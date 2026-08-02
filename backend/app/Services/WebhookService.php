<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebhookService
{
    public function subscribe(array $data): array
    {
        $id = (string) Str::uuid();
        $secret = 'whsec_'.Str::random(48);

        DB::table('developer.webhook_subscriptions')->insert([
            'id' => $id,
            'api_client_id' => $data['api_client_id'],
            'event_code' => $data['event_code'],
            'endpoint_url' => $data['endpoint_url'],
            'secret_hash' => Hash::make($secret),
            'active' => true,
            'max_attempts' => $data['max_attempts'] ?? 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'id' => $id,
            'secret' => $secret,
        ];
    }

    public function publish(
        string $eventCode,
        string $entityType,
        string $entityId,
        array $payload
    ): string {
        $eventId = (string) Str::uuid();

        DB::table('developer.events')->insert([
            'id' => $eventId,
            'event_code' => $eventCode,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'payload' => json_encode($payload),
            'occurred_at' => now(),
        ]);

        $subscriptions = DB::table('developer.webhook_subscriptions')
            ->where('event_code', $eventCode)
            ->where('active', true)
            ->get();

        foreach ($subscriptions as $subscription) {
            DB::table('developer.webhook_deliveries')->insert([
                'id' => (string) Str::uuid(),
                'subscription_id' => $subscription->id,
                'event_code' => $eventCode,
                'event_id' => $eventId,
                'payload' => json_encode($payload),
                'status' => 'pending',
                'attempts' => 0,
                'next_attempt_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $eventId;
    }

    public function deliverPending(int $limit = 100): int
    {
        $deliveries = DB::table('developer.webhook_deliveries as deliveries')
            ->join(
                'developer.webhook_subscriptions as subscriptions',
                'subscriptions.id',
                '=',
                'deliveries.subscription_id'
            )
            ->where('deliveries.status', 'pending')
            ->where(function ($query): void {
                $query->whereNull('deliveries.next_attempt_at')
                    ->orWhere('deliveries.next_attempt_at', '<=', now());
            })
            ->orderBy('deliveries.created_at')
            ->limit($limit)
            ->get([
                'deliveries.*',
                'subscriptions.endpoint_url',
                'subscriptions.max_attempts',
            ]);

        $processed = 0;

        foreach ($deliveries as $delivery) {
            $attempts = $delivery->attempts + 1;

            try {
                $response = Http::timeout(
                    config('developer.webhook_timeout')
                )
                    ->withHeaders([
                        'X-YellowPages-Event' => $delivery->event_code,
                        'X-YellowPages-Delivery' => $delivery->id,
                    ])
                    ->post(
                        $delivery->endpoint_url,
                        json_decode($delivery->payload, true)
                    );

                $successful = $response->successful();

                DB::table('developer.webhook_deliveries')
                    ->where('id', $delivery->id)
                    ->update([
                        'status' => $successful ? 'delivered' : 'pending',
                        'attempts' => $attempts,
                        'response_status' => $response->status(),
                        'response_body' => mb_substr($response->body(), 0, 5000),
                        'last_error' => $successful ? null : 'Non-successful response.',
                        'next_attempt_at' => $successful
                            ? null
                            : now()->addMinutes(config('developer.webhook_retry_minutes')),
                        'delivered_at' => $successful ? now() : null,
                        'updated_at' => now(),
                    ]);

                DB::table('developer.webhook_subscriptions')
                    ->where('id', $delivery->subscription_id)
                    ->update([
                        $successful ? 'last_success_at' : 'last_failure_at' => now(),
                        'updated_at' => now(),
                    ]);

                if (! $successful && $attempts >= $delivery->max_attempts) {
                    DB::table('developer.webhook_deliveries')
                        ->where('id', $delivery->id)
                        ->update([
                            'status' => 'failed',
                            'next_attempt_at' => null,
                        ]);
                }
            } catch (\Throwable $exception) {
                DB::table('developer.webhook_deliveries')
                    ->where('id', $delivery->id)
                    ->update([
                        'status' => $attempts >= $delivery->max_attempts
                            ? 'failed'
                            : 'pending',
                        'attempts' => $attempts,
                        'last_error' => $exception->getMessage(),
                        'next_attempt_at' => $attempts >= $delivery->max_attempts
                            ? null
                            : now()->addMinutes(config('developer.webhook_retry_minutes')),
                        'updated_at' => now(),
                    ]);
            }

            $processed++;
        }

        return $processed;
    }
}
