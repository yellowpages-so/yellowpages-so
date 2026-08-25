<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class ReviewReputationService
{
    public function createReview(Business $business, User $user, array $data): string
    {
        if (DB::table('reviews.reviews')
            ->where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->exists()) {
            throw new RuntimeException('You have already reviewed this business.');
        }

        $id = (string) Str::uuid();

        $record = [
            'id' => $id,
            'business_id' => $business->id,
            'user_id' => $user->id,
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'body' => $data['body'],
            'verified_customer' => false,
            'helpful_count' => 0,
            'moderation_score' => $this->moderationScore($data['body']),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::transaction(function () use ($record, $business): void {
            DB::table('reviews.reviews')->insert(
                $this->existingColumns('reviews.reviews', $record)
            );
            $this->recalculate($business->id);
        });

        return $id;
    }

    public function reply(string $reviewId, User $user, string $reply): string
    {
        $review = DB::table('reviews.reviews')->where('id', $reviewId)->first();

        if (! $review) {
            throw new RuntimeException('Review not found.');
        }

        $manager = DB::table('directory.business_members')
            ->where('business_id', $review->business_id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if (! $manager) {
            throw new RuntimeException('You do not manage this business.');
        }

        $id = DB::table('reviews.review_replies')
            ->where('review_id', $reviewId)
            ->value('id') ?? (string) Str::uuid();

        DB::table('reviews.review_replies')->updateOrInsert(
            ['review_id' => $reviewId],
            [
                'id' => $id,
                'business_id' => $review->business_id,
                'user_id' => $user->id,
                'reply' => $reply,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return $id;
    }

    public function helpful(string $reviewId, User $user, bool $helpful): void
    {
        $id = DB::table('reviews.review_helpful_votes')
            ->where('review_id', $reviewId)
            ->where('user_id', $user->id)
            ->value('id') ?? (string) Str::uuid();

        DB::table('reviews.review_helpful_votes')->updateOrInsert(
            ['review_id' => $reviewId, 'user_id' => $user->id],
            ['id' => $id, 'helpful' => $helpful, 'created_at' => now()]
        );

        $count = DB::table('reviews.review_helpful_votes')
            ->where('review_id', $reviewId)
            ->where('helpful', true)
            ->count();

        if (Schema::hasColumn('reviews.reviews', 'helpful_count')) {
            DB::table('reviews.reviews')
                ->where('id', $reviewId)
                ->update(['helpful_count' => $count]);
        }

        $businessId = DB::table('reviews.reviews')
            ->where('id', $reviewId)
            ->value('business_id');

        if ($businessId) {
            $this->recalculate($businessId);
        }
    }

    public function report(string $reviewId, User $user, array $data): string
    {
        if (! DB::table('reviews.reviews')->where('id', $reviewId)->exists()) {
            throw new RuntimeException('Review not found.');
        }

        $id = (string) Str::uuid();

        DB::table('reviews.review_reports')->insert(
            $this->existingColumns('reviews.review_reports', [
                'id' => $id,
                'review_id' => $reviewId,
                'reported_by' => $user->id,
                'reason' => $data['reason'],
                'details' => $data['details'] ?? null,
                'status' => 'pending',
                'created_at' => now(),
            ])
        );

        return $id;
    }

    public function moderate(string $reviewId, User $actor, array $data): void
    {
        $status = match ($data['action']) {
        'publish', 'restore' => 'approved',      
         'hide' => 'hidden',
            'reject' => 'rejected',
        };

        DB::transaction(function () use ($reviewId, $actor, $data, $status): void {
            DB::table('reviews.reviews')
                ->where('id', $reviewId)
                ->update($this->existingColumns('reviews.reviews', [
                    'status' => $status,
                    'updated_at' => now(),
                ]));

            DB::table('reviews.review_moderation_events')->insert([
                'id' => (string) Str::uuid(),
                'review_id' => $reviewId,
                'actor_user_id' => $actor->id,
                'action' => $data['action'],
                'reason_code' => $data['reason_code'] ?? null,
                'notes' => $data['notes'] ?? null,
                'metadata' => json_encode([]),
                'created_at' => now(),
            ]);
        });

        $businessId = DB::table('reviews.reviews')
            ->where('id', $reviewId)
            ->value('business_id');

        if ($businessId) {
            $this->recalculate($businessId);
        }
    }

    public function recalculate(string $businessId): array
    {
        $query = DB::table('reviews.reviews')
            ->where('business_id', $businessId);

        if (Schema::hasColumn('reviews.reviews', 'status')) {
           $query->where('status', 'approved');     
     }

        $reviewCount = (clone $query)->count();
        $average = (float) ((clone $query)->avg('rating') ?? 0);

        $verified = Schema::hasColumn('reviews.reviews', 'verified_customer')
            ? (clone $query)->where('verified_customer', true)->count()
            : 0;

        $helpful = DB::table('reviews.review_helpful_votes as votes')
            ->join('reviews.reviews as reviews', 'reviews.id', '=', 'votes.review_id')
            ->where('reviews.business_id', $businessId)
            ->where('votes.helpful', true)
            ->count();

        $score = round(
            (($average / 5) * 70)
            + (min($reviewCount / 25, 1) * 15)
            + (($reviewCount > 0 ? $verified / $reviewCount : 0) * 10)
            + (min($helpful / 50, 1) * 5),
            2
        );

        DB::table('reviews.business_reputation_scores')->updateOrInsert(
            ['business_id' => $businessId],
            [
                'score' => $score,
                'average_rating' => $average,
                'review_count' => $reviewCount,
                'verified_review_count' => $verified,
                'helpful_vote_count' => $helpful,
                'updated_at' => now(),
            ]
        );

        DB::table('directory.businesses')
            ->where('id', $businessId)
            ->update($this->existingColumns('directory.businesses', [
                'average_rating' => $average,
                'review_count' => $reviewCount,
                'updated_at' => now(),
            ]));

        return [
            'score' => $score,
            'average_rating' => $average,
            'review_count' => $reviewCount,
        ];
    }

    private function existingColumns(string $table, array $record): array
    {
        return collect($record)
            ->filter(fn ($value, string $column): bool => Schema::hasColumn($table, $column))
            ->all();
    }

    private function moderationScore(string $body): int
    {
        $score = 0;
        $text = mb_strtolower($body);

        foreach (['http://', 'https://', 'whatsapp me', 'guaranteed money'] as $pattern) {
            if (str_contains($text, $pattern)) {
                $score += 20;
            }
        }

        return min($score, 100);
    }
}
