<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class AdvertisingService
{
    public function createCampaign(User $user, array $data): string
    {
        $allowed = DB::table('directory.business_members')
            ->where('business_id', $data['business_id'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if (! $allowed) {
            throw new RuntimeException('You do not manage this business.');
        }

        $id = (string) Str::uuid();

        DB::table('advertising.campaigns')->insert([
            'id' => $id,
            'business_id' => $data['business_id'],
            'created_by' => $user->id,
            'name' => $data['name'],
            'objective' => $data['objective'],
            'billing_model' => $data['billing_model'],
            'total_budget' => $data['total_budget'],
            'daily_budget' => $data['daily_budget'] ?? null,
            'spent_amount' => 0,
            'currency' => strtoupper($data['currency']),
            'status' => 'draft',
            'starts_on' => $data['starts_on'] ?? null,
            'ends_on' => $data['ends_on'] ?? null,
            'targeting' => json_encode($data['targeting'] ?? []),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    public function createCreative(
        User $user,
        string $campaignId,
        array $data
    ): string {
        $campaign = DB::table('advertising.campaigns')
            ->where('id', $campaignId)
            ->first();

        if (! $campaign) {
            throw new RuntimeException('Campaign not found.');
        }

        $allowed = DB::table('directory.business_members')
            ->where('business_id', $campaign->business_id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if (! $allowed) {
            throw new RuntimeException('You do not manage this business.');
        }

        $id = (string) Str::uuid();

        DB::table('advertising.creatives')->insert([
            'id' => $id,
            'campaign_id' => $campaignId,
            'placement_id' => $data['placement_id'],
            'headline' => $data['headline'],
            'body' => $data['body'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'destination_url' => $data['destination_url'],
            'call_to_action' => $data['call_to_action'],
            'status' => 'pending_review',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('advertising.campaigns')
            ->where('id', $campaignId)
            ->update([
                'status' => 'pending_review',
                'updated_at' => now(),
            ]);

        return $id;
    }

    public function activeCreative(
        string $placementCode,
        array $context = []
    ): ?object {
        $now = now()->toDateString();

        $query = DB::table('advertising.creatives as creatives')
            ->join('advertising.campaigns as campaigns', 'campaigns.id', '=', 'creatives.campaign_id')
            ->join('advertising.placements as placements', 'placements.id', '=', 'creatives.placement_id')
            ->join('directory.businesses as businesses', 'businesses.id', '=', 'campaigns.business_id')
            ->where('placements.code', $placementCode)
            ->where('placements.active', true)
            ->where('campaigns.status', 'active')
            ->where('creatives.status', 'approved')
            ->where(function ($builder) use ($now): void {
                $builder->whereNull('campaigns.starts_on')
                    ->orWhere('campaigns.starts_on', '<=', $now);
            })
            ->where(function ($builder) use ($now): void {
                $builder->whereNull('campaigns.ends_on')
                    ->orWhere('campaigns.ends_on', '>=', $now);
            })
            ->whereColumn('campaigns.spent_amount', '<', 'campaigns.total_budget');

        if (! empty($context['business_id'])) {
            $query->where('campaigns.business_id', '!=', $context['business_id']);
        }

        if (! empty($context['city'])) {
            $query->where(function ($builder) use ($context): void {
                $builder->whereRaw(
                    "campaigns.targeting = '{}'::jsonb"
                )->orWhereRaw(
                    "campaigns.targeting->'cities' ? ?",
                    [$context['city']]
                );
            });
        }

        if (! empty($context['category'])) {
            $query->where(function ($builder) use ($context): void {
                $builder->whereRaw(
                    "campaigns.targeting = '{}'::jsonb"
                )->orWhereRaw(
                    "campaigns.targeting->'categories' ? ?",
                    [$context['category']]
                );
            });
        }

        return $query
            ->select([
                'creatives.id',
                'creatives.headline',
                'creatives.body',
                'creatives.image_url',
                'creatives.destination_url',
                'creatives.call_to_action',
                'campaigns.id as campaign_id',
                'campaigns.business_id',
                'campaigns.billing_model',
                'campaigns.currency',
                'placements.base_price',
                'businesses.trading_name',
            ])
            ->inRandomOrder()
            ->first();
    }

    public function recordEvent(
        object $creative,
        string $eventType,
        Request $request
    ): void {
        if (! in_array($eventType, ['impression', 'click', 'conversion'], true)) {
            throw new RuntimeException('Unsupported advertising event.');
        }

        $cost = match ($creative->billing_model) {
            'cpc' => $eventType === 'click' ? (float) $creative->base_price : 0,
            'cpm' => $eventType === 'impression'
                ? ((float) $creative->base_price / 1000)
                : 0,
            default => 0,
        };

        DB::transaction(function () use (
            $creative,
            $eventType,
            $request,
            $cost
        ): void {
            DB::table('advertising.events')->insert([
                'id' => (string) Str::uuid(),
                'campaign_id' => $creative->campaign_id,
                'creative_id' => $creative->id,
                'business_id' => $creative->business_id,
                'event_type' => $eventType,
                'session_id' => $request->header('X-Session-ID'),
                'ip_hash' => hash('sha256', (string) $request->ip()),
                'user_agent_hash' => hash(
                    'sha256',
                    (string) $request->userAgent()
                ),
                'page_url' => $request->input('page_url'),
                'referrer' => $request->input('referrer'),
                'cost_amount' => $cost,
                'created_at' => now(),
            ]);

            if ($cost > 0) {
                DB::table('advertising.campaigns')
                    ->where('id', $creative->campaign_id)
                    ->increment('spent_amount', $cost);
            }
        });
    }

    public function analytics(User $user): array
    {
        $businessIds = DB::table('directory.business_members')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('business_id');

        $campaignIds = DB::table('advertising.campaigns')
            ->whereIn('business_id', $businessIds)
            ->pluck('id');

        $events = DB::table('advertising.events')
            ->whereIn('campaign_id', $campaignIds);

        $impressions = (clone $events)
            ->where('event_type', 'impression')
            ->count();

        $clicks = (clone $events)
            ->where('event_type', 'click')
            ->count();

        $conversions = (clone $events)
            ->where('event_type', 'conversion')
            ->count();

        return [
            'campaigns' => $campaignIds->count(),
            'impressions' => $impressions,
            'clicks' => $clicks,
            'conversions' => $conversions,
            'ctr' => $impressions > 0
                ? round(($clicks / $impressions) * 100, 2)
                : 0,
            'conversion_rate' => $clicks > 0
                ? round(($conversions / $clicks) * 100, 2)
                : 0,
            'spend' => (float) DB::table('advertising.campaigns')
                ->whereIn('id', $campaignIds)
                ->sum('spent_amount'),
        ];
    }

    public function decide(
        string $campaignId,
        User $actor,
        array $data
    ): void {
        $campaign = DB::table('advertising.campaigns')
            ->where('id', $campaignId)
            ->first();

        if (! $campaign) {
            throw new RuntimeException('Campaign not found.');
        }

        $status = match ($data['decision']) {
            'approve' => 'active',
            'reject' => 'rejected',
            'pause' => 'paused',
        };

        DB::transaction(function () use (
            $campaignId,
            $actor,
            $data,
            $status
        ): void {
            DB::table('advertising.campaigns')
                ->where('id', $campaignId)
                ->update([
                    'status' => $status,
                    'approved_at' => $status === 'active' ? now() : null,
                    'approved_by' => $status === 'active' ? $actor->id : null,
                    'rejection_reason' => $status === 'rejected'
                        ? ($data['reason'] ?? null)
                        : null,
                    'updated_at' => now(),
                ]);

            DB::table('advertising.creatives')
                ->where('campaign_id', $campaignId)
                ->update([
                    'status' => $status === 'active'
                        ? 'approved'
                        : $status,
                    'updated_at' => now(),
                ]);
        });
    }
}
