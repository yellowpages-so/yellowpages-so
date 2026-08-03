<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class AnalyticsReportingService
{
    public function track(array $data, ?User $user = null): void
    {
        DB::table('reporting.events')->insert([
            'user_id' => $user?->id,
            'business_id' => $data['business_id'] ?? null,
            'event_type' => $data['event_type'],
            'source' => $data['source'] ?? 'web',
            'session_id' => $data['session_id'] ?? null,
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'value' => $data['value'] ?? null,
            'currency' => $data['currency'] ?? null,
            'dimensions' => json_encode($data['dimensions'] ?? []),
            'occurred_at' => $data['occurred_at'] ?? now(),
        ]);
    }

    public function dashboard(User $user, string $businessId, int $days): array
    {
        $allowed = DB::table('directory.business_members')
            ->where('business_id', $businessId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if (! $allowed) {
            throw new RuntimeException('You do not manage this business.');
        }

        $from = now()->subDays(max($days, 1) - 1)->toDateString();

        $totals = DB::table('reporting.daily_business_metrics')
            ->where('business_id', $businessId)
            ->where('metric_date', '>=', $from)
            ->selectRaw('
                COALESCE(SUM(profile_views),0) profile_views,
                COALESCE(SUM(search_impressions),0) search_impressions,
                COALESCE(SUM(search_clicks),0) search_clicks,
                COALESCE(SUM(lead_count),0) lead_count,
                COALESCE(SUM(order_count),0) order_count,
                COALESCE(SUM(gross_revenue),0) gross_revenue,
                COALESCE(SUM(ad_spend),0) ad_spend
            ')
            ->first();

        $impressions = (int) $totals->search_impressions;
        $clicks = (int) $totals->search_clicks;
        $views = (int) $totals->profile_views;
        $leads = (int) $totals->lead_count;

        return [
            'period_days' => $days,
            'totals' => $totals,
            'rates' => [
                'search_click_through_rate' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
                'profile_to_lead_rate' => $views > 0 ? round(($leads / $views) * 100, 2) : 0,
            ],
            'series' => DB::table('reporting.daily_business_metrics')
                ->where('business_id', $businessId)
                ->where('metric_date', '>=', $from)
                ->orderBy('metric_date')
                ->get(),
        ];
    }

    public function saveReport(User $user, array $data): string
    {
        $id = (string) Str::uuid();

        DB::table('reporting.saved_reports')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'business_id' => $data['business_id'] ?? null,
            'name' => $data['name'],
            'report_type' => $data['report_type'],
            'filters' => json_encode($data['filters'] ?? []),
            'columns' => json_encode($data['columns'] ?? []),
            'visualization' => $data['visualization'] ?? 'table',
            'shared' => $data['shared'] ?? false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}
