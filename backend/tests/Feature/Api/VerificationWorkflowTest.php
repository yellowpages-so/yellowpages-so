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

class VerificationWorkflowTest extends TestCase
{
    public function test_business_owner_can_submit_verification_request_and_document(): void
    {
        Storage::fake('local');

        $user = User::query()->create([
            'public_id' => 'test-'.Str::ulid(),
            'status' => 'active',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $business = Business::query()->create([
            'public_id' => (string) Str::ulid(),
            'legal_name' => 'Verification Test Limited',
            'trading_name' => 'Verification Test',
            'slug' => 'verification-test-'.Str::lower(Str::random(6)),
            'status' => 'draft',
            'profile_completeness' => 50,
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

        $response = $this->postJson(
            "/api/v1/businesses/{$business->public_id}/verification-requests",
            ['requested_level_code' => 'document_verified']
        );

        $response
            ->assertCreated()
            ->assertJsonPath('success', true);

        $requestId = $response->json('data.id');

        $this->post(
            "/api/v1/verification-requests/{$requestId}/documents",
            [
                'document_type' => 'trade_license',
                'file' => UploadedFile::fake()->create(
                    'license.pdf',
                    100,
                    'application/pdf'
                ),
            ],
            ['Accept' => 'application/json']
        )
            ->assertCreated()
            ->assertJsonPath('success', true);

        DB::table('directory.business_members')
            ->where('business_id', $business->id)
            ->delete();

        DB::table('verification.verification_documents')
            ->where('request_id', $requestId)
            ->delete();

        DB::table('verification.verification_checks')
            ->where('request_id', $requestId)
            ->delete();

        DB::table('verification.verification_history')
            ->where('request_id', $requestId)
            ->delete();

        DB::table('verification.verification_requests')
            ->where('id', $requestId)
            ->delete();

        $business->forceDelete();
        $user->delete();
    }
}
