<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class BusinessOwnerPortalService
{
    public function dashboard(User $user): array
    {
        $businessIds = DB::table('directory.business_members')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('business_id');

        return [
            'businesses' => DB::table('directory.businesses')
                ->whereIn('id', $businessIds)
                ->whereNull('deleted_at')
                ->select([
                    'public_id',
                    'trading_name',
                    'slug',
                    'status',
                    'profile_completeness',
                    'verification_level_id',
                    'average_rating',
                    'review_count',
                ])
                ->orderByDesc('created_at')
                ->get(),
            'summary' => [
                'total_businesses' => $businessIds->count(),
                'published_businesses' => DB::table('directory.businesses')
                    ->whereIn('id', $businessIds)
                    ->where('status', 'published')
                    ->count(),
                'open_leads' => $this->countOpenLeads($businessIds->all()),
                'pending_reviews' => $this->countPendingReviews(
                    $businessIds->all()
                ),
                'pending_verification' => DB::table('verification.verification_requests')
                    ->whereIn('business_id', $businessIds)
                    ->whereIn('status', ['submitted', 'under_review', 'information_requested'])
                    ->count(),
            ],
        ];
    }

    private function countOpenLeads(array $businessIds): int
    {
        if ($businessIds === []) {
            return 0;
        }

        foreach ([
            'business_id',
            'recipient_business_id',
            'target_business_id',
        ] as $column) {
            if (Schema::hasColumn('leads.leads', $column)) {
                return DB::table('leads.leads')
                    ->whereIn($column, $businessIds)
                    ->whereIn('status', [
                        'new',
                        'open',
                        'assigned',
                    ])
                    ->count();
            }
        }

        return 0;
    }

    private function countPendingReviews(array $businessIds): int
    {
        if ($businessIds === []) {
            return 0;
        }

        if (
            Schema::hasColumn('reviews.reviews', 'business_reply')
        ) {
            return DB::table('reviews.reviews')
                ->whereIn('business_id', $businessIds)
                ->whereNull('business_reply')
                ->count();
        }

        if (
            Schema::hasColumn('reviews.reviews', 'reply_text')
        ) {
            return DB::table('reviews.reviews')
                ->whereIn('business_id', $businessIds)
                ->whereNull('reply_text')
                ->count();
        }

        if (Schema::hasTable('reviews.review_replies')) {
            return DB::table('reviews.reviews as reviews')
                ->leftJoin(
                    'reviews.review_replies as replies',
                    'replies.review_id',
                    '=',
                    'reviews.id'
                )
                ->whereIn('reviews.business_id', $businessIds)
                ->whereNull('replies.id')
                ->count('reviews.id');
        }

        return DB::table('reviews.reviews')
            ->whereIn('business_id', $businessIds)
            ->count();
    }

    public function recalculateProgress(Business $business): array
    {
        $checks = [
            'details' => filled($business->legal_name)
                && filled($business->trading_name)
                && filled($business->short_description),
            'contacts' => DB::table('directory.business_contacts')
                ->where('business_id', $business->id)
                ->where('is_public', true)
                ->exists(),
            'location' => filled($business->primary_city_id)
                || filled($business->primary_address_id)
                || DB::table('directory.business_branches')
                    ->where('business_id', $business->id)
                    ->exists(),
            'services' => DB::table('directory.business_services')
                ->where('business_id', $business->id)
                ->where('active', true)
                ->exists(),
            'hours' => $this->hasOpeningHours($business),
            'media' => filled($business->logo_url)
                || DB::table('directory.business_media')
                    ->where('business_id', $business->id)
                    ->exists(),
            'verification' => filled($business->verification_level_id),
        ];

        $weights = [
            'details' => 20,
            'contacts' => 15,
            'location' => 15,
            'services' => 15,
            'hours' => 10,
            'media' => 10,
            'verification' => 15,
        ];

        $scores = [];

        foreach ($checks as $key => $complete) {
            $scores[$key] = $complete ? $weights[$key] : 0;
        }

        $total = array_sum($scores);
        $missing = collect($checks)
            ->filter(fn (bool $complete): bool => ! $complete)
            ->keys()
            ->values()
            ->all();

        DB::table('directory.business_profile_progress')->updateOrInsert(
            ['business_id' => $business->id],
            [
                'details_score' => $scores['details'],
                'contacts_score' => $scores['contacts'],
                'location_score' => $scores['location'],
                'services_score' => $scores['services'],
                'hours_score' => $scores['hours'],
                'media_score' => $scores['media'],
                'verification_score' => $scores['verification'],
                'total_score' => $total,
                'missing_items' => json_encode($missing),
                'updated_at' => now(),
            ]
        );

        DB::table('directory.businesses')
            ->where('id', $business->id)
            ->update([
                'profile_completeness' => $total,
                'updated_at' => now(),
            ]);

        return [
            'scores' => $scores,
            'total_score' => $total,
            'missing_items' => $missing,
        ];
    }

    private function hasOpeningHours(Business $business): bool
    {
        if (
            Schema::hasColumn(
                'directory.business_opening_hours',
                'business_id'
            )
        ) {
            return DB::table('directory.business_opening_hours')
                ->where('business_id', $business->id)
                ->exists();
        }

        if (
            Schema::hasColumn(
                'directory.business_opening_hours',
                'branch_id'
            )
        ) {
            $branchIds = DB::table('directory.business_branches')
                ->where('business_id', $business->id)
                ->pluck('id');

            if ($branchIds->isEmpty()) {
                return false;
            }

            return DB::table('directory.business_opening_hours')
                ->whereIn('branch_id', $branchIds)
                ->exists();
        }

        return false;
    }

    public function assertManager(User $user, Business $business): void
    {
        $allowed = DB::table('directory.business_members')
            ->where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if (! $allowed) {
            throw new RuntimeException('You do not manage this business.');
        }
    }

    public function addBranch(Business $business, array $data): string
    {
        $id = (string) Str::uuid();

        DB::table('directory.business_branches')->insert([
            'id' => $id,
            'business_id' => $business->id,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']) ?: 'branch',
            'address_id' => $data['address_id'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'is_head_office' => $data['is_head_office'] ?? false,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}
