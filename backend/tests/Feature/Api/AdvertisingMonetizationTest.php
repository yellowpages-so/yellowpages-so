<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdvertisingMonetizationTest extends TestCase
{
    public function test_owner_can_create_campaign(): void
    {
        $user = User::query()->create([
            'public_id' => 'test-'.Str::ulid(),
            'status' => 'active',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $business = Business::query()->create([
            'public_id' => (string) Str::ulid(),
            'legal_name' => 'Advertising Test Limited',
            'trading_name' => 'Advertising Test',
            'slug' => 'advertising-test-'.Str::lower(Str::random(6)),
            'status' => 'published',
            'profile_completeness' => 100,
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

        $response = $this->postJson('/api/v1/advertising/campaigns', [
            'business_id' => $business->id,
            'name' => 'Homepage campaign',
            'objective' => 'visibility',
            'billing_model' => 'fixed',
            'total_budget' => 100,
            'currency' => 'USD',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true);

        $campaignId = $response->json('data.id');

        DB::table('advertising.creatives')
            ->where('campaign_id', $campaignId)
            ->delete();

        DB::table('advertising.campaigns')
            ->where('id', $campaignId)
            ->delete();

        DB::table('directory.business_members')
            ->where('business_id', $business->id)
            ->delete();

        $business->forceDelete();
        $user->delete();
    }
}
