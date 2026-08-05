<?php

namespace App\Services;

use App\Contracts\AiProvider;
use App\Models\Business;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class AiBusinessIntelligenceService
{
    public function __construct(
        private readonly AiProvider $provider
    ) {}

    public function generateBusinessDescription(
        Business $business
    ): array {
        $services = DB::table('directory.business_services as bs')
            ->leftJoin('directory.services as services', 'services.id', '=', 'bs.service_id')
            ->where('bs.business_id', $business->id)
            ->where('bs.active', true)
            ->selectRaw('COALESCE(services.name, bs.custom_name) as name')
            ->pluck('name')
            ->filter()
            ->values()
            ->all();

        $city = DB::table('directory.cities')
            ->where('id', $business->primary_city_id)
            ->value('name');

        $result = $this->provider->generateText(
            'business_description',
            [
                'trading_name' => $business->trading_name,
                'city' => $city,
                'services' => $services,
            ]
        );

        $this->storeInsight(
            'business',
            $business->id,
            'generated_description',
            $result['text'],
            $result,
            $result['confidence'] ?? null
        );

        return $result;
    }

    public function summarizeReviews(Business $business): array
    {
        $query = DB::table('reviews.reviews')
            ->where('business_id', $business->id);

        if (Schema::hasColumn('reviews.reviews', 'status')) {
            $query->where('status', 'published');
        }

        $reviews = $query
            ->select(['rating', 'title', 'body'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn ($review): array => (array) $review)
            ->all();

        $result = $this->provider->generateText(
            'review_summary',
            ['reviews' => $reviews]
        );

        $this->storeInsight(
            'business',
            $business->id,
            'review_summary',
            $result['text'],
            $result,
            $result['confidence'] ?? null
        );

        return $result;
    }

    public function scoreLead(string $quoteRequestId): array
    {
        $lead = DB::table('leads.quote_requests')
            ->where('id', $quoteRequestId)
            ->first();

        if (! $lead) {
            throw new RuntimeException('Quote request not found.');
        }

        $result = $this->provider->score(
            'lead_score',
            (array) $lead
        );

        DB::table('analytics.lead_scores')->updateOrInsert(
            ['quote_request_id' => $quoteRequestId],
            [
                'score' => $result['score'],
                'grade' => $result['grade'],
                'factors' => json_encode($result['factors']),
                'model_provider' => $result['provider'],
                'model_name' => $result['model'],
                'updated_at' => now(),
            ]
        );

        DB::table('leads.quote_requests')
            ->where('id', $quoteRequestId)
            ->update([
                'lead_score' => $result['score'],
                'updated_at' => now(),
            ]);

        return $result;
    }

    public function scanRisk(
        string $entityType,
        string $entityId,
        string $text
    ): array {
        $result = $this->provider->score(
            'fraud_risk',
            ['text' => $text]
        );

        foreach ($result['factors'] as $signalCode) {
            DB::table('moderation.ai_risk_signals')->insert([
                'id' => (string) Str::uuid(),
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'signal_code' => $signalCode,
                'severity' => $result['score'],
                'confidence' => $result['confidence'],
                'evidence' => json_encode(['text_excerpt' => mb_substr($text, 0, 250)]),
                'status' => 'open',
                'created_at' => now(),
            ]);
        }

        return $result;
    }

    public function rebuildRecommendations(
        string $businessId,
        int $limit = 10
    ): array {
        $business = DB::table('directory.businesses')
            ->where('id', $businessId)
            ->first();

        if (! $business) {
            throw new RuntimeException('Business not found.');
        }

        $categoryIds = DB::table('directory.business_categories')
            ->where('business_id', $businessId)
            ->pluck('category_id');

        $candidates = DB::table('directory.businesses as businesses')
            ->where('businesses.id', '!=', $businessId)
            ->where('businesses.status', 'published')
            ->whereNull('businesses.deleted_at')
            ->when(
                $business->primary_city_id,
                fn ($query) => $query->where('businesses.primary_city_id', $business->primary_city_id)
            )
            ->whereExists(function ($query) use ($categoryIds): void {
                $query->selectRaw('1')
                    ->from('directory.business_categories as bc')
                    ->whereColumn('bc.business_id', 'businesses.id')
                    ->whereIn('bc.category_id', $categoryIds);
            })
            ->orderByDesc('businesses.average_rating')
            ->orderByDesc('businesses.profile_completeness')
            ->limit($limit)
            ->get([
                'businesses.id',
                'businesses.trading_name',
                'businesses.average_rating',
                'businesses.profile_completeness',
            ]);

        DB::table('analytics.business_recommendations')
            ->where('business_id', $businessId)
            ->delete();

        $output = [];

        foreach ($candidates as $index => $candidate) {
            $score = max(1 - ($index * 0.07), 0.10);
            $id = (string) Str::uuid();

            DB::table('analytics.business_recommendations')->insert([
                'id' => $id,
                'business_id' => $businessId,
                'recommended_business_id' => $candidate->id,
                'score' => $score,
                'reasons' => json_encode([
                    'same_city',
                    'shared_category',
                    'quality_ranking',
                ]),
                'generated_at' => now(),
                'expires_at' => now()->addDays(7),
            ]);

            $output[] = [
                'id' => $candidate->id,
                'trading_name' => $candidate->trading_name,
                'score' => $score,
            ];
        }

        return $output;
    }

    public function dashboard(): array
    {
        return [
            'generated_insights' => DB::table('analytics.ai_insights')->count(),
            'open_risk_signals' => DB::table('moderation.ai_risk_signals')
                ->where('status', 'open')
                ->count(),
            'high_risk_signals' => DB::table('moderation.ai_risk_signals')
                ->where('status', 'open')
                ->where('severity', '>=', 60)
                ->count(),
            'scored_leads' => DB::table('analytics.lead_scores')->count(),
            'recommendation_pairs' => DB::table('analytics.business_recommendations')->count(),
        ];
    }

    private function storeInsight(
        string $entityType,
        string $entityId,
        string $insightType,
        ?string $summary,
        array $payload,
        ?float $confidence
    ): void {
        $existingId = DB::table('analytics.ai_insights')
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('insight_type', $insightType)
            ->value('id');

        DB::table('analytics.ai_insights')->updateOrInsert(
            [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'insight_type' => $insightType,
            ],
            [
                'id' => $existingId ?? (string) Str::uuid(),
                'summary' => $summary,
                'payload' => json_encode($payload),
                'confidence' => $confidence,
                'model_provider' => $payload['provider'] ?? config('ai.provider'),
                'model_name' => $payload['model'] ?? config('ai.model'),
                'status' => 'ready',
                'generated_at' => now(),
                'expires_at' => now()->addHours((int) config('ai.cache_hours')),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
