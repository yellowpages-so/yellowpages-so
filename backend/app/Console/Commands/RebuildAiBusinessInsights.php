<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\AiBusinessIntelligenceService;
use Illuminate\Console\Command;

class RebuildAiBusinessInsights extends Command
{
    protected $signature = 'ai:rebuild-business-insights
        {--limit=100}
        {--recommendations}';

    protected $description = 'Rebuild AI summaries and recommendations for published businesses';

    public function handle(
        AiBusinessIntelligenceService $service
    ): int {
        $limit = max((int) $this->option('limit'), 1);

        Business::query()
            ->where('status', 'published')
            ->limit($limit)
            ->each(function (Business $business) use ($service): void {
                $service->generateBusinessDescription($business);
                $service->summarizeReviews($business);

                if ($this->option('recommendations')) {
                    $service->rebuildRecommendations($business->id);
                }

                $this->line("Processed {$business->trading_name}");
            });

        return self::SUCCESS;
    }
}
