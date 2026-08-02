<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class LeadMarketplaceService
{
    public function create(?User $user, array $data): array
    {
        return DB::transaction(function () use ($user, $data): array {
            $id = (string) Str::uuid();
            $reference = 'RFQ-'.now()->format('Ymd').'-'.strtoupper(Str::random(8));

            DB::table('leads.quote_requests')->insert([
                'id' => $id,
                'reference_no' => $reference,
                'customer_user_id' => $user?->id,
                'category_id' => $data['category_id'] ?? null,
                'city_id' => $data['city_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'],
                'budget_currency' => $data['budget_currency'] ?? null,
                'budget_min' => $data['budget_min'] ?? null,
                'budget_max' => $data['budget_max'] ?? null,
                'required_by' => $data['required_by'] ?? null,
                'contact_name' => $data['contact_name'] ?? null,
                'contact_email' => $data['contact_email'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'preferred_contact' => $data['preferred_contact'],
                'status' => 'open',
                'lead_score' => $this->score($data),
                'expires_at' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $businessIds = collect($data['business_ids'] ?? [])
                ->filter()
                ->unique()
                ->values();

            if ($businessIds->isEmpty()) {
                $businessIds = $this->matchBusinesses(
                    $data['category_id'] ?? null,
                    $data['city_id'] ?? null
                );
            }

            foreach ($businessIds as $businessId) {
                DB::table('leads.quote_request_businesses')->insert([
                    'id' => (string) Str::uuid(),
                    'quote_request_id' => $id,
                    'business_id' => $businessId,
                    'status' => 'new',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->activity(
                $id,
                null,
                $user?->id,
                'quote_request_created',
                ['business_count' => $businessIds->count()]
            );

            return [
                'id' => $id,
                'reference_no' => $reference,
                'matched_businesses' => $businessIds->count(),
            ];
        });
    }

    public function inbox(User $user, array $filters): mixed
    {
        $businessIds = DB::table('directory.business_members')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('business_id');

        return DB::table('leads.quote_request_businesses as assignments')
            ->join('leads.quote_requests as requests', 'requests.id', '=', 'assignments.quote_request_id')
            ->join('directory.businesses as businesses', 'businesses.id', '=', 'assignments.business_id')
            ->leftJoin('directory.categories as categories', 'categories.id', '=', 'requests.category_id')
            ->leftJoin('directory.cities as cities', 'cities.id', '=', 'requests.city_id')
            ->whereIn('assignments.business_id', $businessIds)
            ->when(
                $filters['status'] ?? null,
                fn ($query, string $status) => $query->where('assignments.status', $status)
            )
            ->select([
                'assignments.id as assignment_id',
                'assignments.status as assignment_status',
                'assignments.viewed_at',
                'assignments.responded_at',
                'assignments.created_at as assigned_at',
                'requests.id',
                'requests.reference_no',
                'requests.title',
                'requests.description',
                'requests.budget_currency',
                'requests.budget_min',
                'requests.budget_max',
                'requests.required_by',
                'requests.preferred_contact',
                'requests.lead_score',
                'requests.created_at',
                'businesses.public_id as business_public_id',
                'businesses.trading_name',
                'categories.name as category_name',
                'cities.name as city_name',
            ])
            ->orderByDesc('requests.lead_score')
            ->orderByDesc('requests.created_at')
            ->paginate(25);
    }

    public function respond(
        string $quoteRequestId,
        string $businessId,
        User $user,
        array $data
    ): string {
        $allowed = DB::table('directory.business_members')
            ->where('business_id', $businessId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if (! $allowed) {
            throw new RuntimeException('You do not manage this business.');
        }

        $assignment = DB::table('leads.quote_request_businesses')
            ->where('quote_request_id', $quoteRequestId)
            ->where('business_id', $businessId)
            ->first();

        if (! $assignment) {
            throw new RuntimeException('This lead is not assigned to the business.');
        }

        return DB::transaction(function () use (
            $quoteRequestId,
            $businessId,
            $user,
            $data,
            $assignment
        ): string {
            $id = (string) Str::uuid();

            DB::table('leads.quote_responses')->insert([
                'id' => $id,
                'quote_request_id' => $quoteRequestId,
                'business_id' => $businessId,
                'user_id' => $user->id,
                'message' => $data['message'],
                'currency' => $data['currency'] ?? null,
                'amount' => $data['amount'] ?? null,
                'estimated_days' => $data['estimated_days'] ?? null,
                'status' => 'submitted',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('leads.quote_request_businesses')
                ->where('id', $assignment->id)
                ->update([
                    'status' => 'quoted',
                    'responded_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->activity(
                $quoteRequestId,
                $businessId,
                $user->id,
                'quote_submitted',
                ['response_id' => $id]
            );

            return $id;
        });
    }

    public function updateStatus(
        string $assignmentId,
        User $user,
        array $data
    ): void {
        $assignment = DB::table('leads.quote_request_businesses')
            ->where('id', $assignmentId)
            ->first();

        if (! $assignment) {
            throw new RuntimeException('Lead assignment not found.');
        }

        $allowed = DB::table('directory.business_members')
            ->where('business_id', $assignment->business_id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if (! $allowed) {
            throw new RuntimeException('You do not manage this business.');
        }

        DB::table('leads.quote_request_businesses')
            ->where('id', $assignmentId)
            ->update([
                'status' => $data['status'],
                'business_note' => $data['note'] ?? null,
                'viewed_at' => $data['status'] === 'viewed'
                    ? now()
                    : $assignment->viewed_at,
                'closed_at' => in_array(
                    $data['status'],
                    ['won', 'lost', 'closed'],
                    true
                ) ? now() : null,
                'updated_at' => now(),
            ]);

        $this->activity(
            $assignment->quote_request_id,
            $assignment->business_id,
            $user->id,
            'lead_status_changed',
            [
                'status' => $data['status'],
                'note' => $data['note'] ?? null,
            ]
        );
    }

    public function analytics(User $user): array
    {
        $businessIds = DB::table('directory.business_members')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('business_id');

        $base = DB::table('leads.quote_request_businesses')
            ->whereIn('business_id', $businessIds);

        return [
            'total' => (clone $base)->count(),
            'new' => (clone $base)->where('status', 'new')->count(),
            'quoted' => (clone $base)->where('status', 'quoted')->count(),
            'won' => (clone $base)->where('status', 'won')->count(),
            'lost' => (clone $base)->where('status', 'lost')->count(),
            'conversion_rate' => ($total = (clone $base)->count()) > 0
                ? round(((clone $base)->where('status', 'won')->count() / $total) * 100, 2)
                : 0,
        ];
    }

    private function matchBusinesses(?string $categoryId, ?string $cityId): mixed
    {
        return DB::table('directory.businesses as businesses')
            ->when(
                $cityId,
                fn ($query) => $query->where('businesses.primary_city_id', $cityId)
            )
            ->when(
                $categoryId,
                function ($query) use ($categoryId): void {
                    $query->whereExists(function ($subquery) use ($categoryId): void {
                        $subquery
                            ->selectRaw('1')
                            ->from('directory.business_categories as bc')
                            ->whereColumn('bc.business_id', 'businesses.id')
                            ->where('bc.category_id', $categoryId);
                    });
                }
            )
            ->where('businesses.status', 'published')
            ->whereNull('businesses.deleted_at')
            ->orderByDesc('businesses.verification_level_id')
            ->orderByDesc('businesses.profile_completeness')
            ->limit(5)
            ->pluck('businesses.id');
    }

    private function score(array $data): int
    {
        $score = 20;

        if (! empty($data['budget_min']) || ! empty($data['budget_max'])) {
            $score += 20;
        }

        if (! empty($data['required_by'])) {
            $score += 15;
        }

        if (! empty($data['category_id'])) {
            $score += 15;
        }

        if (! empty($data['city_id'])) {
            $score += 10;
        }

        if (
            ! empty($data['contact_email'])
            || ! empty($data['contact_phone'])
        ) {
            $score += 20;
        }

        return min($score, 100);
    }

    private function activity(
        string $quoteRequestId,
        ?string $businessId,
        ?string $actorUserId,
        string $eventType,
        array $metadata
    ): void {
        DB::table('leads.lead_activity')->insert([
            'id' => (string) Str::uuid(),
            'quote_request_id' => $quoteRequestId,
            'business_id' => $businessId,
            'actor_user_id' => $actorUserId,
            'event_type' => $eventType,
            'metadata' => json_encode($metadata),
            'created_at' => now(),
        ]);
    }
}
