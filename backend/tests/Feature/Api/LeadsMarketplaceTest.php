<?php

namespace Tests\Feature\Api;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeadsMarketplaceTest extends TestCase
{
public function test_guest_can_submit_quote_request(): void
{
    $response = $this->postJson('/api/v1/quote-requests', [
        'title' => 'Need motor insurance',
        'description' => 'I need motor insurance for a commercial vehicle in Mogadishu.',
        'contact_name' => 'Test Customer',
        'contact_email' => 'customer@example.com',
        'preferred_contact' => 'email',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('success', true);

    $id = $response->json('data.id');

    $this->assertDatabaseHas('leads.quote_requests', [
        'id' => $id,
        'status' => 'open',
    ]);

    DB::table('leads.lead_activity')
        ->where('quote_request_id', $id)
        ->delete();

    DB::table('leads.quote_request_businesses')
        ->where('quote_request_id', $id)
        ->delete();

    DB::table('leads.quote_requests')
        ->where('id', $id)
        ->delete();
}
public function test_owner_cannot_mark_lead_won_without_customer_acceptance(): void
{
    $user = User::query()->create([
        'public_id' => 'test-'.Str::ulid(),
        'status' => 'active',
        'password_hash' => Hash::make('Password123!'),
    ]);

    $business = Business::query()->create([
        'public_id' => (string) Str::ulid(),
        'legal_name' => 'Lead Guard Test Limited',
        'trading_name' => 'Lead Guard Test',
        'slug' => 'lead-guard-test-'.Str::lower(Str::random(6)),
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

    $quoteRequestId = (string) Str::uuid();
    $assignmentId = (string) Str::uuid();

    DB::table('leads.quote_requests')->insert([
        'id' => $quoteRequestId,
        'reference_no' => 'TEST-'.Str::upper(Str::random(8)),
        'title' => 'Owner won guard test',
        'description' => 'Test request',
        'status' => 'open',
        'contact_name' => 'Test Customer',
        'contact_email' => 'customer@example.com',
        'preferred_contact' => 'email',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('leads.quote_request_businesses')->insert([
        'id' => $assignmentId,
        'quote_request_id' => $quoteRequestId,
        'business_id' => $business->id,
        'status' => 'new',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    try {
        app(\App\Services\LeadMarketplaceService::class)
            ->updateStatus(
                $assignmentId,
                $user,
                [
                    'status' => 'won',
                    'note' => null,
                ]
            );

        $this->fail(
            'Expected RuntimeException was not thrown.'
        );
    } catch (\RuntimeException $e) {
        $this->assertSame(
            'A lead can only be marked as won when the customer accepts the quote.',
            $e->getMessage()
        );
    } finally {
        DB::table('leads.lead_activity')
            ->where('quote_request_id', $quoteRequestId)
            ->delete();

        DB::table('leads.quote_request_businesses')
            ->where('id', $assignmentId)
            ->delete();

        DB::table('leads.quote_requests')
            ->where('id', $quoteRequestId)
            ->delete();

        DB::table('directory.business_members')
            ->where('business_id', $business->id)
            ->delete();

        $business->forceDelete();
        $user->delete();
    }
}
}
