<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnalyticsReportingTest extends TestCase
{
    public function test_owner_can_open_reporting_dashboard(): void
    {
        $user = User::query()->create([
            'public_id' => 'test-'.Str::ulid(),
            'status' => 'active',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $business = Business::query()->create([
            'public_id' => (string) Str::ulid(),
            'legal_name' => 'Reporting Test Limited',
            'trading_name' => 'Reporting Test',
            'slug' => 'reporting-test-'.Str::lower(Str::random(6)),
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

        DB::table('reporting.daily_business_metrics')->insert([
            'business_id' => $business->id,
            'metric_date' => now()->toDateString(),
            'profile_views' => 100,
            'search_impressions' => 250,
            'search_clicks' => 50,
            'lead_count' => 10,
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/reporting/businesses/{$business->id}/dashboard")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.rates.search_click_through_rate', 20);

        DB::table('reporting.daily_business_metrics')->where('business_id', $business->id)->delete();
        DB::table('directory.business_members')->where('business_id', $business->id)->delete();
        $business->forceDelete();
        $user->delete();
    }
}
