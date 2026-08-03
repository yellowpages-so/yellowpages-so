<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReviewsReputationTest extends TestCase
{
    public function test_user_can_submit_review(): void
    {
        $user = User::query()->create([
            'public_id' => 'test-'.Str::ulid(),
            'status' => 'active',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $business = Business::query()->create([
            'public_id' => (string) Str::ulid(),
            'legal_name' => 'Review Test Limited',
            'trading_name' => 'Review Test',
            'slug' => 'review-test-'.Str::lower(Str::random(6)),
            'status' => 'published',
            'profile_completeness' => 90,
            'created_by' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson(
            "/api/v1/businesses/{$business->public_id}/reviews",
            [
                'rating' => 5,
                'title' => 'Excellent service',
                'body' => 'Professional and responsive customer service.',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('success', true);

        $reviewId = $response->json('data.id');

        $this->getJson("/api/v1/businesses/{$business->public_id}/reviews")
            ->assertOk()
            ->assertJsonPath('success', true);

        DB::table('reviews.business_reputation_scores')
            ->where('business_id', $business->id)
            ->delete();

        DB::table('reviews.reviews')
            ->where('id', $reviewId)
            ->delete();

        $business->forceDelete();
        $user->delete();
    }
}
