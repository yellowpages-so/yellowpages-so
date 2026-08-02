<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Meilisearch\Client;
use Throwable;

class SmartSearchService
{
    public function search(array $filters): array
    {
        if (config('search.driver') === 'meilisearch') {
            try {
                return $this->searchMeilisearch($filters);
            } catch (Throwable $exception) {
                report($exception);

                if (! config('search.fallback_to_database')) {
                    throw $exception;
                }
            }
        }

        return $this->searchDatabase($filters);
    }

    public function suggestions(string $query, int $limit = 8): array
    {
        if (mb_strlen(trim($query)) < 2) {
            return [];
        }

        $result = $this->search([
            'q' => $query,
            'limit' => $limit,
        ]);

        return collect($result['hits'] ?? [])
            ->map(fn (array $hit): array => [
                'id' => $hit['public_id'] ?? $hit['id'],
                'label' => $hit['trading_name'],
                'slug' => $hit['slug'],
                'city' => $hit['city'] ?? null,
                'type' => 'business',
            ])
            ->values()
            ->all();
    }

    private function searchMeilisearch(array $filters): array
    {
        $client = new Client(
            config('search.host'),
            config('search.key') ?: null
        );

        $options = [
            'limit' => min((int) ($filters['limit'] ?? 20), 50),
            'offset' => max((int) ($filters['offset'] ?? 0), 0),
            'attributesToHighlight' => [
                'trading_name',
                'short_description',
            ],
            'highlightPreTag' => '<mark>',
            'highlightPostTag' => '</mark>',
        ];

        $conditions = [];

        foreach (['city_slug', 'district_slug', 'category_slugs', 'status'] as $field) {
            $input = match ($field) {
                'city_slug' => $filters['city'] ?? null,
                'district_slug' => $filters['district'] ?? null,
                'category_slugs' => $filters['category'] ?? null,
                'status' => $filters['status'] ?? 'published',
            };

            if ($input) {
                $escaped = str_replace('"', '\"', (string) $input);
                $conditions[] = "{$field} = \"{$escaped}\"";
            }
        }

        if (! empty($filters['verified'])) {
            $conditions[] = 'verified = true';
        }

        if ($conditions !== []) {
            $options['filter'] = implode(' AND ', $conditions);
        }

        if (
            isset($filters['latitude'], $filters['longitude'], $filters['radius_km'])
        ) {
            $radius = max((float) $filters['radius_km'], 0.1) * 1000;
            $options['filter'] = trim(
                ($options['filter'] ?? '').' AND _geoRadius('.
                (float) $filters['latitude'].', '.
                (float) $filters['longitude'].', '.
                $radius.')',
                ' AND'
            );
        }

        $response = $client
            ->index(config('search.index'))
            ->search(trim((string) ($filters['q'] ?? '')), $options);

        return [
            'hits' => $response->getHits(),
            'query' => $response->getQuery(),
            'processing_time_ms' => $response->getProcessingTimeMs(),
            'estimated_total_hits' => $response->getEstimatedTotalHits(),
            'source' => 'meilisearch',
        ];
    }

    private function searchDatabase(array $filters): array
    {
        $query = DB::table('directory.businesses as businesses')
            ->leftJoin(
                'directory.cities as cities',
                'cities.id',
                '=',
                'businesses.primary_city_id'
            )
            ->whereNull('businesses.deleted_at')
            ->where('businesses.status', $filters['status'] ?? 'published');

        if (! empty($filters['q'])) {
            $term = trim($filters['q']);

            $query->where(function ($builder) use ($term): void {
                $builder
                    ->where('businesses.trading_name', 'ilike', "%{$term}%")
                    ->orWhere('businesses.legal_name', 'ilike', "%{$term}%")
                    ->orWhere('businesses.short_description', 'ilike', "%{$term}%");
            });
        }

        if (! empty($filters['city'])) {
            $query->where('cities.slug', $filters['city']);
        }

        $limit = min((int) ($filters['limit'] ?? 20), 50);
        $offset = max((int) ($filters['offset'] ?? 0), 0);

        $hits = $query
            ->select([
                'businesses.public_id',
                'businesses.trading_name',
                'businesses.slug',
                'businesses.short_description',
                'businesses.logo_url',
                'businesses.average_rating',
                'businesses.review_count',
                'businesses.verification_level_id',
                'cities.name as city',
                'cities.slug as city_slug',
            ])
            ->orderByDesc('businesses.profile_completeness')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn ($row): array => [
                ...((array) $row),
                'verified' => $row->verification_level_id !== null,
            ])
            ->all();

        return [
            'hits' => $hits,
            'query' => $filters['q'] ?? '',
            'processing_time_ms' => null,
            'estimated_total_hits' => count($hits),
            'source' => 'database',
        ];
    }
}
