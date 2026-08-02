<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Meilisearch\Client;

class RebuildBusinessSearchIndex extends Command
{
    protected $signature = 'search:rebuild-businesses
        {--chunk=500}
        {--status=published}';

    protected $description = 'Rebuild the Meilisearch business index';

    public function handle(): int
    {
        $client = new Client(
            config('search.host'),
            config('search.key') ?: null
        );

        $index = $client->index(config('search.index'));

        $index->updateSettings([
            'searchableAttributes' => [
                'trading_name',
                'legal_name',
                'short_description',
                'description',
                'category_names',
                'service_names',
                'keywords',
                'city',
                'district',
            ],
            'filterableAttributes' => [
                'status',
                'verified',
                'city_slug',
                'district_slug',
                'category_slugs',
                '_geo',
            ],
            'sortableAttributes' => [
                'average_rating',
                'review_count',
                'profile_completeness',
                'created_at_timestamp',
            ],
            'rankingRules' => [
                'words',
                'typo',
                'proximity',
                'attribute',
                'sort',
                'exactness',
                'verified:desc',
                'profile_completeness:desc',
            ],
            'synonyms' => [
                'mogadishu' => ['muqdisho', 'mogadiscio'],
                'muqdisho' => ['mogadishu', 'mogadiscio'],
                'kismayo' => ['kismaayo'],
                'garowe' => ['garoowe'],
                'baidoa' => ['baydhabo'],
                'insurance' => ['caymis', 'takaful'],
                'hospital' => ['isbitaal', 'medical centre', 'clinic'],
                'pharmacy' => ['farmashiye', 'chemist'],
                'restaurant' => ['makhaayad', 'cafe'],
            ],
            'typoTolerance' => [
                'enabled' => true,
                'disableOnAttributes' => ['public_id', 'slug'],
                'minWordSizeForTypos' => [
                    'oneTypo' => 5,
                    'twoTypos' => 9,
                ],
            ],
        ]);

        $index->deleteAllDocuments();

        $status = $this->option('status');
        $chunk = max((int) $this->option('chunk'), 50);
        $count = 0;

        DB::table('directory.businesses as businesses')
            ->leftJoin('directory.cities as cities', 'cities.id', '=', 'businesses.primary_city_id')
            ->leftJoin('directory.addresses as addresses', 'addresses.id', '=', 'businesses.primary_address_id')
            ->leftJoin('directory.districts as districts', 'districts.id', '=', 'addresses.district_id')
            ->whereNull('businesses.deleted_at')
            ->when($status, fn ($query) => $query->where('businesses.status', $status))
            ->select([
                'businesses.*',
                'cities.name as city',
                'cities.slug as city_slug',
                'districts.name as district',
                'districts.slug as district_slug',
            ])
            ->orderBy('businesses.id')
            ->chunk($chunk, function ($rows) use ($index, &$count): void {
                $businessIds = $rows->pluck('id');

                $categories = DB::table('directory.business_categories as bc')
                    ->join('directory.categories as c', 'c.id', '=', 'bc.category_id')
                    ->whereIn('bc.business_id', $businessIds)
                    ->get(['bc.business_id', 'c.name', 'c.name_so', 'c.slug'])
                    ->groupBy('business_id');

                $services = DB::table('directory.business_services as bs')
                    ->leftJoin('directory.services as s', 's.id', '=', 'bs.service_id')
                    ->whereIn('bs.business_id', $businessIds)
                    ->where('bs.active', true)
                    ->get(['bs.business_id', 's.name', 's.name_so', 'bs.custom_name'])
                    ->groupBy('business_id');

                $documents = $rows->map(function ($row) use ($categories, $services): array {
                    $businessCategories = $categories->get($row->id, collect());
                    $businessServices = $services->get($row->id, collect());

                    $document = [
                        'id' => $row->public_id,
                        'public_id' => $row->public_id,
                        'trading_name' => $row->trading_name,
                        'legal_name' => $row->legal_name,
                        'slug' => $row->slug,
                        'short_description' => $row->short_description,
                        'description' => $row->description,
                        'logo_url' => $row->logo_url,
                        'status' => $row->status,
                        'verified' => $row->verification_level_id !== null,
                        'average_rating' => (float) ($row->average_rating ?? 0),
                        'review_count' => (int) ($row->review_count ?? 0),
                        'profile_completeness' => (int) ($row->profile_completeness ?? 0),
                        'city' => $row->city,
                        'city_slug' => $row->city_slug,
                        'district' => $row->district,
                        'district_slug' => $row->district_slug,
                        'category_names' => $businessCategories
                            ->flatMap(fn ($item) => [$item->name, $item->name_so])
                            ->filter()
                            ->values()
                            ->all(),
                        'category_slugs' => $businessCategories
                            ->pluck('slug')
                            ->filter()
                            ->values()
                            ->all(),
                        'service_names' => $businessServices
                            ->flatMap(fn ($item) => [
                                $item->name,
                                $item->name_so,
                                $item->custom_name,
                            ])
                            ->filter()
                            ->values()
                            ->all(),
                        'keywords' => [],
                        'created_at_timestamp' => strtotime((string) $row->created_at),
                    ];

                    if (
                        isset($row->latitude, $row->longitude)
                        && $row->latitude !== null
                        && $row->longitude !== null
                    ) {
                        $document['_geo'] = [
                            'lat' => (float) $row->latitude,
                            'lng' => (float) $row->longitude,
                        ];
                    }

                    return $document;
                })->all();

                $index->addDocuments($documents, 'id');
                $count += count($documents);
                $this->info("Queued {$count} businesses.");
            });

        $this->info("Search rebuild submitted for {$count} businesses.");

        return self::SUCCESS;
    }
}
