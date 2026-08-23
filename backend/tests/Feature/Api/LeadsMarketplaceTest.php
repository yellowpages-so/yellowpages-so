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
public function test_business_cannot_submit_duplicate_quote_response(): void
{
    $user = User::query()->create([
        'public_id' => 'test-'.Str::ulid(),
        'status' => 'active',
        'password_hash' => Hash::make('Password123!'),
    ]);

    $business = Business::query()->create([
        'public_id' => (string) Str::ulid(),
        'legal_name' => 'Duplicate Quote Test Limited',
        'trading_name' => 'Duplicate Quote Test',
        'slug' => 'duplicate-quote-test-'.Str::lower(Str::random(6)),
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
        'title' => 'Duplicate quote guard test',
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
        $service = app(\App\Services\LeadMarketplaceService::class);

        $service->respond(
            $quoteRequestId,
            $business->id,
            $user,
            [
                'message' => 'First quote',
                'currency' => 'USD',
                'amount' => 100,
                'estimated_days' => 2,
            ]
        );

        try {
            $service->respond(
                $quoteRequestId,
                $business->id,
                $user,
                [
                    'message' => 'Second quote',
                    'currency' => 'USD',
                    'amount' => 120,
                    'estimated_days' => 3,
                ]
            );

            $this->fail(
                'Expected RuntimeException was not thrown.'
            );
        } catch (\RuntimeException $e) {
            $this->assertSame(
                'A quote has already been submitted for this lead.',
                $e->getMessage()
            );
        }
    } finally {
        DB::table('leads.lead_activity')
            ->where('quote_request_id', $quoteRequestId)
            ->delete();

        DB::table('leads.quote_responses')
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
public function test_customer_acceptance_marks_chosen_business_won_and_others_lost(): void
{
    $customer = User::query()->create([
        'public_id' => 'test-'.Str::ulid(),
        'status' => 'active',
        'password_hash' => Hash::make('Password123!'),
    ]);

    $owner = User::query()->create([
        'public_id' => 'test-'.Str::ulid(),
        'status' => 'active',
        'password_hash' => Hash::make('Password123!'),
    ]);

    $chosenBusiness = Business::query()->create([
        'public_id' => (string) Str::ulid(),
        'legal_name' => 'Chosen Quote Test Limited',
        'trading_name' => 'Chosen Quote Test',
        'slug' => 'chosen-quote-test-'.Str::lower(Str::random(6)),
        'status' => 'published',
        'profile_completeness' => 100,
        'created_by' => $owner->id,
    ]);

    $otherBusiness = Business::query()->create([
        'public_id' => (string) Str::ulid(),
        'legal_name' => 'Other Quote Test Limited',
        'trading_name' => 'Other Quote Test',
        'slug' => 'other-quote-test-'.Str::lower(Str::random(6)),
        'status' => 'published',
        'profile_completeness' => 100,
        'created_by' => $owner->id,
    ]);

    DB::table('directory.business_members')->insert([
        [
            'id' => (string) Str::uuid(),
            'business_id' => $chosenBusiness->id,
            'user_id' => $owner->id,
            'role_code' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
            'created_at' => now(),
        ],
        [
            'id' => (string) Str::uuid(),
            'business_id' => $otherBusiness->id,
            'user_id' => $owner->id,
            'role_code' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
            'created_at' => now(),
        ],
    ]);

    $quoteRequestId = (string) Str::uuid();
    $chosenAssignmentId = (string) Str::uuid();
    $otherAssignmentId = (string) Str::uuid();
    $responseId = (string) Str::uuid();

    DB::table('leads.quote_requests')->insert([
        'id' => $quoteRequestId,
        'reference_no' => 'TEST-'.Str::upper(Str::random(8)),
        'title' => 'Customer acceptance test',
        'description' => 'Test request',
        'status' => 'open',
        'customer_user_id' => $customer->id,
        'contact_name' => 'Test Customer',
        'contact_email' => 'customer@example.com',
        'preferred_contact' => 'email',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('leads.quote_request_businesses')->insert([
        [
            'id' => $chosenAssignmentId,
            'quote_request_id' => $quoteRequestId,
            'business_id' => $chosenBusiness->id,
            'status' => 'quoted',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => $otherAssignmentId,
            'quote_request_id' => $quoteRequestId,
            'business_id' => $otherBusiness->id,
            'status' => 'new',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    DB::table('leads.quote_responses')->insert([
        'id' => $responseId,
        'quote_request_id' => $quoteRequestId,
        'business_id' => $chosenBusiness->id,
        'user_id' => $owner->id,
        'message' => 'Accepted test quote',
        'currency' => 'USD',
        'amount' => 150,
        'estimated_days' => 2,
        'status' => 'submitted',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    try {
        app(\App\Services\LeadMarketplaceService::class)
            ->acceptQuote(
                $customer,
                $quoteRequestId,
                $responseId
            );

        $this->assertDatabaseHas(
            'leads.quote_request_businesses',
            [
                'id' => $chosenAssignmentId,
                'status' => 'won',
            ]
        );

        $this->assertDatabaseHas(
            'leads.quote_request_businesses',
            [
                'id' => $otherAssignmentId,
                'status' => 'lost',
            ]
        );

        $this->assertDatabaseHas(
            'leads.quote_requests',
            [
                'id' => $quoteRequestId,
                'status' => 'closed',
            ]
        );

        $this->assertDatabaseHas(
            'leads.lead_activity',
            [
                'quote_request_id' => $quoteRequestId,
                'business_id' => $chosenBusiness->id,
                'event_type' => 'quote_accepted',
            ]
        );
    } finally {
        DB::table('leads.lead_activity')
            ->where('quote_request_id', $quoteRequestId)
            ->delete();

        DB::table('leads.quote_responses')
            ->where('quote_request_id', $quoteRequestId)
            ->delete();

        DB::table('leads.quote_request_businesses')
            ->where('quote_request_id', $quoteRequestId)
            ->delete();

        DB::table('leads.quote_requests')
            ->where('id', $quoteRequestId)
            ->delete();

        DB::table('directory.business_members')
            ->whereIn(
                'business_id',
                [
                    $chosenBusiness->id,
                    $otherBusiness->id,
                ]
            )
            ->delete();

        $chosenBusiness->forceDelete();
        $otherBusiness->forceDelete();
        $owner->delete();
        $customer->delete();
    }
}
public function test_customer_decline_marks_only_selected_business_lost_and_keeps_request_open(): void
{
$customer = User::query()->create([
'public_id' => 'test-'.Str::ulid(),
'status' => 'active',
'password_hash' => Hash::make('Password123!'),
]);
$owner = User::query()->create([
    'public_id' => 'test-'.Str::ulid(),
    'status' => 'active',
    'password_hash' => Hash::make('Password123!'),
]);

$declinedBusiness = Business::query()->create([
    'public_id' => (string) Str::ulid(),
    'legal_name' => 'Declined Quote Test Limited',
    'trading_name' => 'Declined Quote Test',
    'slug' => 'declined-quote-test-'.Str::lower(Str::random(6)),
    'status' => 'published',
    'profile_completeness' => 100,
    'created_by' => $owner->id,
]);

$otherBusiness = Business::query()->create([
    'public_id' => (string) Str::ulid(),
    'legal_name' => 'Remaining Quote Test Limited',
    'trading_name' => 'Remaining Quote Test',
    'slug' => 'remaining-quote-test-'.Str::lower(Str::random(6)),
    'status' => 'published',
    'profile_completeness' => 100,
    'created_by' => $owner->id,
]);

DB::table('directory.business_members')->insert([
    [
        'id' => (string) Str::uuid(),
        'business_id' => $declinedBusiness->id,
        'user_id' => $owner->id,
        'role_code' => 'owner',
        'status' => 'active',
        'joined_at' => now(),
        'created_at' => now(),
    ],
    [
        'id' => (string) Str::uuid(),
        'business_id' => $otherBusiness->id,
        'user_id' => $owner->id,
        'role_code' => 'owner',
        'status' => 'active',
        'joined_at' => now(),
        'created_at' => now(),
    ],
]);

$quoteRequestId = (string) Str::uuid();
$declinedAssignmentId = (string) Str::uuid();
$otherAssignmentId = (string) Str::uuid();
$responseId = (string) Str::uuid();

DB::table('leads.quote_requests')->insert([
    'id' => $quoteRequestId,
    'reference_no' => 'TEST-'.Str::upper(Str::random(8)),
    'title' => 'Customer decline test',
    'description' => 'Test request',
    'status' => 'open',
    'customer_user_id' => $customer->id,
    'contact_name' => 'Test Customer',
    'contact_email' => 'customer@example.com',
    'preferred_contact' => 'email',
    'created_at' => now(),
    'updated_at' => now(),
]);

DB::table('leads.quote_request_businesses')->insert([
    [
        'id' => $declinedAssignmentId,
        'quote_request_id' => $quoteRequestId,
        'business_id' => $declinedBusiness->id,
        'status' => 'quoted',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'id' => $otherAssignmentId,
        'quote_request_id' => $quoteRequestId,
        'business_id' => $otherBusiness->id,
        'status' => 'new',
        'created_at' => now(),
        'updated_at' => now(),
    ],
]);

DB::table('leads.quote_responses')->insert([
    'id' => $responseId,
    'quote_request_id' => $quoteRequestId,
    'business_id' => $declinedBusiness->id,
    'user_id' => $owner->id,
    'message' => 'Declined test quote',
    'currency' => 'USD',
    'amount' => 100,
    'estimated_days' => 2,
    'status' => 'submitted',
    'created_at' => now(),
    'updated_at' => now(),
]);

try {
    app(\App\Services\LeadMarketplaceService::class)
        ->declineQuote(
            $customer,
            $quoteRequestId,
            $responseId
        );

    $this->assertDatabaseHas(
        'leads.quote_request_businesses',
        [
            'id' => $declinedAssignmentId,
            'status' => 'lost',
        ]
    );

    $this->assertDatabaseHas(
        'leads.quote_request_businesses',
        [
            'id' => $otherAssignmentId,
            'status' => 'new',
        ]
    );

    $this->assertDatabaseHas(
        'leads.quote_requests',
        [
            'id' => $quoteRequestId,
            'status' => 'open',
        ]
    );

    $this->assertDatabaseHas(
        'leads.lead_activity',
        [
            'quote_request_id' => $quoteRequestId,
            'business_id' => $declinedBusiness->id,
            'event_type' => 'quote_declined',
        ]
    );
} finally {
    DB::table('leads.lead_activity')
        ->where('quote_request_id', $quoteRequestId)
        ->delete();

    DB::table('leads.quote_responses')
        ->where('quote_request_id', $quoteRequestId)
        ->delete();

    DB::table('leads.quote_request_businesses')
        ->where('quote_request_id', $quoteRequestId)
        ->delete();

    DB::table('leads.quote_requests')
        ->where('id', $quoteRequestId)
        ->delete();

    DB::table('directory.business_members')
        ->whereIn(
            'business_id',
            [
                $declinedBusiness->id,
                $otherBusiness->id,
            ]
        )
        ->delete();

    $declinedBusiness->forceDelete();
    $otherBusiness->forceDelete();
    $owner->delete();
    $customer->delete();
}
}
public function test_customer_cannot_accept_another_customers_quote_request(): void
{
    $ownerCustomer = User::query()->create([
        'public_id' => 'test-'.Str::ulid(),
        'status' => 'active',
        'password_hash' => Hash::make('Password123!'),
    ]);

    $otherCustomer = User::query()->create([
        'public_id' => 'test-'.Str::ulid(),
        'status' => 'active',
        'password_hash' => Hash::make('Password123!'),
    ]);

    $owner = User::query()->create([
        'public_id' => 'test-'.Str::ulid(),
        'status' => 'active',
        'password_hash' => Hash::make('Password123!'),
    ]);

    $business = Business::query()->create([
        'public_id' => (string) Str::ulid(),
        'legal_name' => 'Authorization Quote Test Limited',
        'trading_name' => 'Authorization Quote Test',
        'slug' => 'authorization-quote-test-'.Str::lower(Str::random(6)),
        'status' => 'published',
        'profile_completeness' => 100,
        'created_by' => $owner->id,
    ]);

    DB::table('directory.business_members')->insert([
        'id' => (string) Str::uuid(),
        'business_id' => $business->id,
        'user_id' => $owner->id,
        'role_code' => 'owner',
        'status' => 'active',
        'joined_at' => now(),
        'created_at' => now(),
    ]);

    $quoteRequestId = (string) Str::uuid();
    $assignmentId = (string) Str::uuid();
    $responseId = (string) Str::uuid();

    DB::table('leads.quote_requests')->insert([
        'id' => $quoteRequestId,
        'reference_no' => 'TEST-'.Str::upper(Str::random(8)),
        'title' => 'Customer authorization test',
        'description' => 'Test request',
        'status' => 'open',
        'customer_user_id' => $ownerCustomer->id,
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
        'status' => 'quoted',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('leads.quote_responses')->insert([
        'id' => $responseId,
        'quote_request_id' => $quoteRequestId,
        'business_id' => $business->id,
        'user_id' => $owner->id,
        'message' => 'Authorization test quote',
        'currency' => 'USD',
        'amount' => 100,
        'estimated_days' => 2,
        'status' => 'submitted',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    try {
        try {
            app(\App\Services\LeadMarketplaceService::class)
                ->acceptQuote(
                    $otherCustomer,
                    $quoteRequestId,
                    $responseId
                );

            $this->fail(
                'Expected RuntimeException was not thrown.'
            );
        } catch (\RuntimeException $e) {
            $this->assertSame(
                'Quote request not found.',
                $e->getMessage()
            );
        }

        $this->assertDatabaseHas(
            'leads.quote_requests',
            [
                'id' => $quoteRequestId,
                'status' => 'open',
            ]
        );

        $this->assertDatabaseHas(
            'leads.quote_request_businesses',
            [
                'id' => $assignmentId,
                'status' => 'quoted',
            ]
        );
    } finally {
        DB::table('leads.lead_activity')
            ->where('quote_request_id', $quoteRequestId)
            ->delete();

        DB::table('leads.quote_responses')
            ->where('quote_request_id', $quoteRequestId)
            ->delete();

        DB::table('leads.quote_request_businesses')
            ->where('quote_request_id', $quoteRequestId)
            ->delete();

        DB::table('leads.quote_requests')
            ->where('id', $quoteRequestId)
            ->delete();

        DB::table('directory.business_members')
            ->where('business_id', $business->id)
            ->delete();

        $business->forceDelete();
        $owner->delete();
        $otherCustomer->delete();
        $ownerCustomer->delete();
    }
}
public function test_customer_cannot_decline_another_customers_quote_request(): void
{
    $ownerCustomer = User::query()->create([
        'public_id' => 'test-'.Str::ulid(),
        'status' => 'active',
        'password_hash' => Hash::make('Password123!'),
    ]);

    $otherCustomer = User::query()->create([
        'public_id' => 'test-'.Str::ulid(),
        'status' => 'active',
        'password_hash' => Hash::make('Password123!'),
    ]);

    $owner = User::query()->create([
        'public_id' => 'test-'.Str::ulid(),
        'status' => 'active',
        'password_hash' => Hash::make('Password123!'),
    ]);

    $business = Business::query()->create([
        'public_id' => (string) Str::ulid(),
        'legal_name' => 'Authorization Quote Test Limited',
        'trading_name' => 'Authorization Quote Test',
        'slug' => 'authorization-quote-test-'.Str::lower(Str::random(6)),
        'status' => 'published',
        'profile_completeness' => 100,
        'created_by' => $owner->id,
    ]);

    DB::table('directory.business_members')->insert([
        'id' => (string) Str::uuid(),
        'business_id' => $business->id,
        'user_id' => $owner->id,
        'role_code' => 'owner',
        'status' => 'active',
        'joined_at' => now(),
        'created_at' => now(),
    ]);

    $quoteRequestId = (string) Str::uuid();
    $assignmentId = (string) Str::uuid();
    $responseId = (string) Str::uuid();

    DB::table('leads.quote_requests')->insert([
        'id' => $quoteRequestId,
        'reference_no' => 'TEST-'.Str::upper(Str::random(8)),
        'title' => 'Customer authorization test',
        'description' => 'Test request',
        'status' => 'open',
        'customer_user_id' => $ownerCustomer->id,
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
        'status' => 'quoted',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('leads.quote_responses')->insert([
        'id' => $responseId,
        'quote_request_id' => $quoteRequestId,
        'business_id' => $business->id,
        'user_id' => $owner->id,
        'message' => 'Authorization test quote',
        'currency' => 'USD',
        'amount' => 100,
        'estimated_days' => 2,
        'status' => 'submitted',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    try {
        try {
            app(\App\Services\LeadMarketplaceService::class)
                ->acceptQuote(
                    $otherCustomer,
                    $quoteRequestId,
                    $responseId
                );

            $this->fail(
                'Expected RuntimeException was not thrown.'
            );
        } catch (\RuntimeException $e) {
            $this->assertSame(
                'Quote request not found.',
                $e->getMessage()
            );
        }

        $this->assertDatabaseHas(
            'leads.quote_requests',
            [
                'id' => $quoteRequestId,
                'status' => 'open',
            ]
        );

        $this->assertDatabaseHas(
            'leads.quote_request_businesses',
            [
                'id' => $assignmentId,
                'status' => 'quoted',
            ]
        );
    } finally {
        DB::table('leads.lead_activity')
            ->where('quote_request_id', $quoteRequestId)
            ->delete();

        DB::table('leads.quote_responses')
            ->where('quote_request_id', $quoteRequestId)
            ->delete();

        DB::table('leads.quote_request_businesses')
            ->where('quote_request_id', $quoteRequestId)
            ->delete();

        DB::table('leads.quote_requests')
            ->where('id', $quoteRequestId)
            ->delete();

        DB::table('directory.business_members')
            ->where('business_id', $business->id)
            ->delete();

        $business->forceDelete();
        $owner->delete();
        $otherCustomer->delete();
        $ownerCustomer->delete();
    }
}
public function test_customer_cancel_closes_open_request_and_non_terminal_assignments(): void
{
    $customer = User::query()->create([
        'public_id' => 'test-'.Str::ulid(),
        'status' => 'active',
        'password_hash' => Hash::make('Password123!'),
    ]);

    $owner = User::query()->create([
        'public_id' => 'test-'.Str::ulid(),
        'status' => 'active',
        'password_hash' => Hash::make('Password123!'),
    ]);

    $businessOne = Business::query()->create([
        'public_id' => (string) Str::ulid(),
        'legal_name' => 'Cancel Test One Limited',
        'trading_name' => 'Cancel Test One',
        'slug' => 'cancel-test-one-'.Str::lower(Str::random(6)),
        'status' => 'published',
        'profile_completeness' => 100,
        'created_by' => $owner->id,
    ]);

    $businessTwo = Business::query()->create([
        'public_id' => (string) Str::ulid(),
        'legal_name' => 'Cancel Test Two Limited',
        'trading_name' => 'Cancel Test Two',
        'slug' => 'cancel-test-two-'.Str::lower(Str::random(6)),
        'status' => 'published',
        'profile_completeness' => 100,
        'created_by' => $owner->id,
    ]);

    $businessThree = Business::query()->create([
        'public_id' => (string) Str::ulid(),
        'legal_name' => 'Cancel Test Three Limited',
        'trading_name' => 'Cancel Test Three',
        'slug' => 'cancel-test-three-'.Str::lower(Str::random(6)),
        'status' => 'published',
        'profile_completeness' => 100,
        'created_by' => $owner->id,
    ]);

    DB::table('directory.business_members')->insert([
        [
            'id' => (string) Str::uuid(),
            'business_id' => $businessOne->id,
            'user_id' => $owner->id,
            'role_code' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
            'created_at' => now(),
        ],
        [
            'id' => (string) Str::uuid(),
            'business_id' => $businessTwo->id,
            'user_id' => $owner->id,
            'role_code' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
            'created_at' => now(),
        ],
        [
            'id' => (string) Str::uuid(),
            'business_id' => $businessThree->id,
            'user_id' => $owner->id,
            'role_code' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
            'created_at' => now(),
        ],
    ]);

    $quoteRequestId = (string) Str::uuid();
    $assignmentOne = (string) Str::uuid();
    $assignmentTwo = (string) Str::uuid();
    $assignmentThree = (string) Str::uuid();

    DB::table('leads.quote_requests')->insert([
        'id' => $quoteRequestId,
        'reference_no' => 'TEST-'.Str::upper(Str::random(8)),
        'title' => 'Customer cancel test',
        'description' => 'Test request',
        'status' => 'open',
        'customer_user_id' => $customer->id,
        'contact_name' => 'Test Customer',
        'contact_email' => 'customer@example.com',
        'preferred_contact' => 'email',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('leads.quote_request_businesses')->insert([
        [
            'id' => $assignmentOne,
            'quote_request_id' => $quoteRequestId,
            'business_id' => $businessOne->id,
            'status' => 'new',
            'closed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => $assignmentTwo,
            'quote_request_id' => $quoteRequestId,
            'business_id' => $businessTwo->id,
            'status' => 'quoted',
            'closed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => $assignmentThree,
            'quote_request_id' => $quoteRequestId,
            'business_id' => $businessThree->id,
            'status' => 'lost',
            'closed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    try {
        app(\App\Services\LeadMarketplaceService::class)
            ->cancelQuoteRequest(
                $customer,
                $quoteRequestId
            );

        $this->assertDatabaseHas('leads.quote_requests', [
            'id' => $quoteRequestId,
            'status' => 'closed',
        ]);

        $this->assertDatabaseHas(
            'leads.quote_request_businesses',
            [
                'id' => $assignmentOne,
                'status' => 'closed',
            ]
        );

        $this->assertDatabaseHas(
            'leads.quote_request_businesses',
            [
                'id' => $assignmentTwo,
                'status' => 'closed',
            ]
        );

        $this->assertDatabaseHas(
            'leads.quote_request_businesses',
            [
                'id' => $assignmentThree,
                'status' => 'lost',
            ]
        );

        $this->assertDatabaseHas('leads.lead_activity', [
            'quote_request_id' => $quoteRequestId,
            'event_type' => 'quote_request_cancelled',
        ]);
    } finally {
        DB::table('leads.lead_activity')
            ->where('quote_request_id', $quoteRequestId)
            ->delete();

        DB::table('leads.quote_request_businesses')
            ->where('quote_request_id', $quoteRequestId)
            ->delete();

        DB::table('leads.quote_requests')
            ->where('id', $quoteRequestId)
            ->delete();

        DB::table('directory.business_members')
            ->whereIn('business_id', [
                $businessOne->id,
                $businessTwo->id,
                $businessThree->id,
            ])
            ->delete();

        $businessOne->forceDelete();
        $businessTwo->forceDelete();
        $businessThree->forceDelete();
        $owner->delete();
        $customer->delete();
    }
}
public function test_customer_cannot_cancel_another_customers_quote_request(): void
{
    $ownerCustomer = User::query()->create([
        'public_id' => 'test-'.Str::ulid(),
        'status' => 'active',
        'password_hash' => Hash::make('Password123!'),
    ]);

    $otherCustomer = User::query()->create([
        'public_id' => 'test-'.Str::ulid(),
        'status' => 'active',
        'password_hash' => Hash::make('Password123!'),
    ]);

    $owner = User::query()->create([
        'public_id' => 'test-'.Str::ulid(),
        'status' => 'active',
        'password_hash' => Hash::make('Password123!'),
    ]);

    $business = Business::query()->create([
        'public_id' => (string) Str::ulid(),
        'legal_name' => 'Cancel Authorization Test Limited',
        'trading_name' => 'Cancel Authorization Test',
        'slug' => 'cancel-authorization-test-'.Str::lower(Str::random(6)),
        'status' => 'published',
        'profile_completeness' => 100,
        'created_by' => $owner->id,
    ]);

    DB::table('directory.business_members')->insert([
        'id' => (string) Str::uuid(),
        'business_id' => $business->id,
        'user_id' => $owner->id,
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
        'title' => 'Cancel authorization test',
        'description' => 'Test request',
        'status' => 'open',
        'customer_user_id' => $ownerCustomer->id,
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
        try {
            app(\App\Services\LeadMarketplaceService::class)
                ->cancelQuoteRequest(
                    $otherCustomer,
                    $quoteRequestId
                );

            $this->fail(
                'Expected RuntimeException was not thrown.'
            );
        } catch (\RuntimeException $e) {
            $this->assertSame(
                'Quote request not found.',
                $e->getMessage()
            );
        }

        $this->assertDatabaseHas(
            'leads.quote_requests',
            [
                'id' => $quoteRequestId,
                'status' => 'open',
            ]
        );

        $this->assertDatabaseHas(
            'leads.quote_request_businesses',
            [
                'id' => $assignmentId,
                'status' => 'new',
            ]
        );
    } finally {
        DB::table('leads.lead_activity')
            ->where('quote_request_id', $quoteRequestId)
            ->delete();

        DB::table('leads.quote_request_businesses')
            ->where('quote_request_id', $quoteRequestId)
            ->delete();

        DB::table('leads.quote_requests')
            ->where('id', $quoteRequestId)
            ->delete();

        DB::table('directory.business_members')
            ->where('business_id', $business->id)
            ->delete();

        $business->forceDelete();
        $owner->delete();
        $otherCustomer->delete();
        $ownerCustomer->delete();
    }
}
}
