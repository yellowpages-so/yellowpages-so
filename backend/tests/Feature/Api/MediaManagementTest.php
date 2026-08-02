<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MediaManagementTest extends TestCase
{
    public function test_owner_can_upload_business_logo(): void
    {
        Storage::fake('public');

        $user = User::query()->create([
            'public_id' => 'test-'.Str::ulid(),
            'status' => 'active',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $business = Business::query()->create([
            'public_id' => (string) Str::ulid(),
            'legal_name' => 'Media Test Limited',
            'trading_name' => 'Media Test',
            'slug' => 'media-test-'.Str::lower(Str::random(6)),
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

        $response = $this->postJson('/api/v1/media', [
            'file' => UploadedFile::fake()->image(
                'logo.jpg',
                500,
                500
            ),
            'owner_type' => 'business',
            'owner_id' => $business->id,
            'business_id' => $business->id,
            'collection' => 'logo',
            'visibility' => 'public',
            'alt_text' => 'Media Test logo',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true);

        $assetId = $response->json('data.id');

        $this->assertDatabaseHas('media.assets', [
            'id' => $assetId,
            'business_id' => $business->id,
            'collection' => 'logo',
        ]);

        DB::table('media.access_logs')
            ->where('asset_id', $assetId)
            ->delete();

        DB::table('media.processing_jobs')
            ->where('asset_id', $assetId)
            ->delete();

        DB::table('media.asset_tags')
            ->where('asset_id', $assetId)
            ->delete();

        DB::table('media.asset_variants')
            ->where('asset_id', $assetId)
            ->delete();

        DB::table('media.assets')
            ->where('id', $assetId)
            ->delete();

        DB::table('directory.business_members')
            ->where('business_id', $business->id)
            ->delete();

        $business->forceDelete();
        $user->delete();
    }
}
