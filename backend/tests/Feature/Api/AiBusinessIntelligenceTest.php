<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiBusinessIntelligenceTest extends TestCase
{
    public function test_owner_can_generate_business_description(): void
    {
        $user = User::query()->create([
            'public_id' => 'test-'.Str::ulid(),
            'status' => 'active',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $business = Business::query()->create([
            'public_id' => (string) Str::ulid(),
            'legal_name' => 'AI Test Limited',
            'trading_name' => 'AI Test',
            'slug' => 'ai-test-'.Str::lower(Str::random(6)),
            'status' => 'published',
            'profile_completeness' => 80,
            'created_by' => $user->id,
        ]);

        DB::table('directory.business_members')->insert([
            'id' => (string) Str::uuid(),
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role_code' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
            'created_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson(
            "/api/v1/ai/businesses/{$business->public_id}/generate-description"
        )
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('analytics.ai_insights', [
            'entity_type' => 'business',
            'entity_id' => $business->id,
            'insight_type' => 'generated_description',
        ]);

        DB::table('analytics.ai_insights')
            ->where('entity_id', $business->id)
            ->delete();

        DB::table('analytics.business_recommendations')
            ->where('business_id', $business->id)
            ->orWhere('recommended_business_id', $business->id)
            ->delete();

        DB::table('directory.business_members')
            ->where('business_id', $business->id)
            ->delete();

        $business->forceDelete();
        $user->delete();
    }
}
