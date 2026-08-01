<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BusinessCrudTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::query()->create([
            'public_id' => 'test-'.Str::ulid(),
            'status' => 'active',
            'password_hash' => Hash::make('YellowPages2026!'),
        ]);

        DB::table('iam.user_profiles')->insert([
            'user_id' => $this->user->id,
            'first_name' => 'Business',
            'last_name' => 'Owner',
            'display_name' => 'Business Owner',
            'locale' => 'en',
            'timezone' => 'Africa/Mogadishu',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->user);
    }

    protected function tearDown(): void
    {
        DB::table('directory.business_members')
            ->where('user_id', $this->user->id)
            ->delete();

        DB::table('directory.businesses')
            ->where('created_by', $this->user->id)
            ->delete();

        DB::table('iam.user_profiles')
            ->where('user_id', $this->user->id)
            ->delete();

        DB::table('iam.users')
            ->where('id', $this->user->id)
            ->delete();

        parent::tearDown();
    }

    public function test_owner_can_create_list_update_and_archive_business(): void
    {
        $create = $this->postJson('/api/businesses', [
            'legal_name' => 'Horn Islamic Insurance Company Limited',
            'trading_name' => 'Horn Islamic Insurance',
            'short_description' => 'Sharia-compliant insurance.',
            'established_year' => 2012,
            'website_url' => 'https://horninsurance.so',
        ]);

        $create
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.trading_name', 'Horn Islamic Insurance');

        $publicId = $create->json('data.id');

        $this->getJson('/api/businesses')
            ->assertOk()
            ->assertJsonPath('data.0.id', $publicId);

        $this->patchJson("/api/businesses/{$publicId}", [
            'short_description' => 'Updated insurance profile.',
        ])
            ->assertOk()
            ->assertJsonPath('data.short_description', 'Updated insurance profile.');

        $this->deleteJson("/api/businesses/{$publicId}")
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
