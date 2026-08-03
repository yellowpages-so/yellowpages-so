<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CustomerSupportService
{
    public function createTicket(?User $user, array $data): array
    {
        $queue = DB::table('support.queues')
            ->where('code', $data['queue_code'] ?? 'general')
            ->where('active', true)
            ->first();

        if (! $queue) {
            throw new RuntimeException('Support queue is unavailable.');
        }

        $sla = DB::table('support.sla_policies')
            ->where('queue_id', $queue->id)
            ->where('priority', $data['priority'] ?? 'normal')
            ->where('active', true)
            ->first();

        $id = (string) Str::uuid();
        $ticketNo = 'SUP-'.now()->format('Ymd').'-'.strtoupper(Str::random(8));

        DB::transaction(function () use (
            $user,
            $data,
            $queue,
            $sla,
            $id,
            $ticketNo
        ): void {
            DB::table('support.tickets')->insert([
                'id' => $id,
                'ticket_no' => $ticketNo,
                'requester_user_id' => $user?->id,
                'business_id' => $data['business_id'] ?? null,
                'queue_id' => $queue->id,
                'subject' => $data['subject'],
                'description' => $data['description'],
                'channel' => $data['channel'] ?? 'web',
                'priority' => $data['priority'] ?? 'normal',
                'status' => 'open',
                'requester_name' => $data['requester_name'] ?? null,
                'requester_email' => $data['requester_email'] ?? null,
                'requester_phone' => $data['requester_phone'] ?? null,
                'first_response_due_at' => $sla
                    ? now()->addMinutes($sla->first_response_minutes)
                    : null,
                'resolution_due_at' => $sla
                    ? now()->addMinutes($sla->resolution_minutes)
                    : null,
                'metadata' => json_encode($data['metadata'] ?? []),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('support.ticket_messages')->insert([
                'id' => (string) Str::uuid(),
                'ticket_id' => $id,
                'user_id' => $user?->id,
                'sender_type' => $user ? 'customer' : 'guest',
                'body' => $data['description'],
                'internal' => false,
                'metadata' => json_encode([]),
                'created_at' => now(),
            ]);

            $this->event(
                $id,
                $user?->id,
                'ticket_created',
                null,
                ['status' => 'open']
            );
        });

        return [
            'id' => $id,
            'ticket_no' => $ticketNo,
            'status' => 'open',
        ];
    }

    public function reply(
        User $user,
        string $ticketId,
        string $body,
        bool $internal = false
    ): string {
        $ticket = DB::table('support.tickets')
            ->where('id', $ticketId)
            ->first();

        if (! $ticket) {
            throw new RuntimeException('Ticket not found.');
        }

        $id = (string) Str::uuid();

        DB::transaction(function () use (
            $user,
            $ticket,
            $ticketId,
            $body,
            $internal,
            $id
        ): void {
            DB::table('support.ticket_messages')->insert([
                'id' => $id,
                'ticket_id' => $ticketId,
                'user_id' => $user->id,
                'sender_type' => 'agent',
                'body' => $body,
                'internal' => $internal,
                'metadata' => json_encode([]),
                'created_at' => now(),
            ]);

            if (! $internal && ! $ticket->first_responded_at) {
                DB::table('support.tickets')
                    ->where('id', $ticketId)
                    ->update([
                        'first_responded_at' => now(),
                        'status' => 'pending_customer',
                        'updated_at' => now(),
                    ]);
            }

            $this->event(
                $ticketId,
                $user->id,
                $internal ? 'internal_note_added' : 'agent_replied',
                null,
                ['message_id' => $id]
            );
        });

        return $id;
    }

    public function updateTicket(
        User $user,
        string $ticketId,
        array $data
    ): void {
        $ticket = DB::table('support.tickets')
            ->where('id', $ticketId)
            ->first();

        if (! $ticket) {
            throw new RuntimeException('Ticket not found.');
        }

        $changes = array_filter([
            'status' => $data['status'] ?? null,
            'priority' => $data['priority'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'queue_id' => $data['queue_id'] ?? null,
        ], fn ($value) => $value !== null);

        if (($data['status'] ?? null) === 'resolved') {
            $changes['resolved_at'] = now();
        }

        if (($data['status'] ?? null) === 'closed') {
            $changes['closed_at'] = now();
        }

        $changes['updated_at'] = now();

        DB::transaction(function () use (
            $ticket,
            $ticketId,
            $user,
            $changes
        ): void {
            DB::table('support.tickets')
                ->where('id', $ticketId)
                ->update($changes);

            $this->event(
                $ticketId,
                $user->id,
                'ticket_updated',
                (array) $ticket,
                $changes
            );
        });
    }

    public function publicArticles(?string $query = null): mixed
    {
        return DB::table('support.knowledge_articles')
            ->where('status', 'published')
            ->when(
                $query,
                fn ($builder, string $value) => $builder
                    ->where('title', 'ilike', "%{$value}%")
            )
            ->orderByDesc('published_at')
            ->paginate(20);
    }

    private function event(
        string $ticketId,
        ?string $actorUserId,
        string $eventType,
        ?array $before,
        ?array $after
    ): void {
        DB::table('support.ticket_events')->insert([
            'id' => (string) Str::uuid(),
            'ticket_id' => $ticketId,
            'actor_user_id' => $actorUserId,
            'event_type' => $eventType,
            'before' => $before ? json_encode($before) : null,
            'after' => $after ? json_encode($after) : null,
            'created_at' => now(),
        ]);
    }
}
