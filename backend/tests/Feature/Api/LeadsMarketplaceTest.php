<?php

namespace Tests\Feature\Api;

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
}
