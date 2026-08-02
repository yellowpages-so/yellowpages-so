<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BusinessOwnerCapabilitiesTest extends TestCase
{
    public function test_owner_can_view_dashboard_and_add_contact(): void
    {
        $user = User::query()->create([
            'public_id' => 'test-'.Str::ulid(),
            'status' => 'active',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $business = Business::query()->create([
            'public_id' => (string) Str::ulid(),
            'legal_name' => 'Owner Portal Test Limited',
            'trading_name' => 'Owner Portal Test',
            'slug' => 'owner-portal-test-'.Str::lower(Str::random(6)),
            'status' => 'draft',
            'profile_completeness' => 20,
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

        $this->getJson('/api/v1/owner/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->postJson(
            "/api/v1/owner/businesses/{$business->public_id}/contacts",
            [
                'contact_type' => 'phone',
                'value' => '+252611234567',
                'is_primary' => true,
                'is_public' => true,
            ]
        )
            ->assertCreated()
            ->assertJsonPath('success', true);

        DB::table('directory.business_contacts')
            ->where('business_id', $business->id)
            ->delete();

        DB::table('directory.business_profile_progress')
            ->where('business_id', $business->id)
            ->delete();

        DB::table('directory.business_members')
            ->where('business_id', $business->id)
            ->delete();

        $business->forceDelete();
        $user->delete();
    }
}
