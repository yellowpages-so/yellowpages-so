<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerSupportTest extends TestCase
{
    public function test_user_can_create_support_ticket(): void
    {
        if (! DB::table('support.queues')->where('code', 'general')->exists()) {
            $queueId = (string) Str::uuid();

            DB::table('support.queues')->insert([
                'id' => $queueId,
                'code' => 'general',
                'name' => 'General Support',
                'active' => true,
                'default_sla_minutes' => 1440,
                'created_at' => now(),
            ]);

            DB::table('support.sla_policies')->insert([
                'id' => (string) Str::uuid(),
                'queue_id' => $queueId,
                'priority' => 'normal',
                'first_response_minutes' => 480,
                'resolution_minutes' => 1440,
                'active' => true,
                'created_at' => now(),
            ]);
        }

        $user = User::query()->create([
            'public_id' => 'test-'.Str::ulid(),
            'status' => 'active',
            'password_hash' => Hash::make('Password123!'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/support/tickets', [
            'subject' => 'Unable to update listing',
            'description' => 'The business listing form does not save.',
            'priority' => 'normal',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true);

        $ticketId = $response->json('data.id');

        $this->assertDatabaseHas('support.tickets', [
            'id' => $ticketId,
            'requester_user_id' => $user->id,
        ]);

        DB::table('support.ticket_attachments')
            ->where('ticket_id', $ticketId)
            ->delete();

        DB::table('support.ticket_events')
            ->where('ticket_id', $ticketId)
            ->delete();

        DB::table('support.ticket_messages')
            ->where('ticket_id', $ticketId)
            ->delete();

        DB::table('support.surveys')
            ->where('ticket_id', $ticketId)
            ->delete();

        DB::table('support.tickets')
            ->where('id', $ticketId)
            ->delete();

        $user->delete();
    }
}
