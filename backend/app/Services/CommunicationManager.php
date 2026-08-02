<?php

namespace App\Services;

use App\Contracts\CommunicationChannel;
use App\Services\Communications\LogChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CommunicationManager
{
    public function queue(
        string $eventCode,
        array $recipient,
        array $variables = [],
        ?string $businessId = null,
        array $channels = []
    ): array {
        $channels = $channels !== []
            ? $channels
            : config('communications.default_channels');

        $created = [];

        foreach ($channels as $channel) {
            if (! $this->channelEnabled(
                $recipient['user_id'] ?? null,
                $eventCode,
                $channel
            )) {
                continue;
            }

            if ($channel === 'in_app') {
                $created[] = $this->createInApp(
                    $eventCode,
                    $recipient,
                    $variables
                );

                continue;
            }

            $template = DB::table('notifications.templates')
                ->where('code', $eventCode)
                ->where('channel', $channel)
                ->where('active', true)
                ->first();

            if (! $template) {
                $template = DB::table('notifications.templates')
                    ->where('code', $eventCode)
                    ->where('channel', 'email')
                    ->where('active', true)
                    ->first();
            }

            if (! $template) {
                throw new RuntimeException(
                    "No notification template found for {$eventCode}."
                );
            }

            $messageId = (string) Str::uuid();

            DB::table('notifications.messages')->insert([
                'id' => $messageId,
                'user_id' => $recipient['user_id'] ?? null,
                'business_id' => $businessId,
                'event_code' => $eventCode,
                'channel' => $channel,
                'recipient' => $this->recipientForChannel(
                    $recipient,
                    $channel
                ),
                'subject' => $this->render(
                    $template->subject,
                    $variables
                ),
                'body' => $this->render(
                    $template->body,
                    $variables
                ),
                'status' => 'pending',
                'attempts' => 0,
                'max_attempts' => config(
                    'communications.max_attempts'
                ),
                'scheduled_at' => now(),
                'metadata' => json_encode([
                    'variables' => $variables,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $created[] = $messageId;
        }

        return $created;
    }

    public function deliver(string $messageId): array
    {
        $message = DB::table('notifications.messages')
            ->where('id', $messageId)
            ->first();

        if (! $message) {
            throw new RuntimeException('Notification message not found.');
        }

        if (in_array($message->status, ['sent', 'delivered'], true)) {
            return [
                'success' => true,
                'status' => $message->status,
            ];
        }

        $attempts = $message->attempts + 1;

        DB::table('notifications.messages')
            ->where('id', $messageId)
            ->update([
                'status' => 'processing',
                'attempts' => $attempts,
                'updated_at' => now(),
            ]);

        try {
            $result = $this->channel(
                $message->channel
            )->send((array) $message);

            DB::table('notifications.messages')
                ->where('id', $messageId)
                ->update([
                    'status' => 'sent',
                    'provider' => $result['provider'] ?? null,
                    'provider_message_id' => $result['provider_message_id'] ?? null,
                    'sent_at' => now(),
                    'last_error' => null,
                    'updated_at' => now(),
                ]);

            $this->deliveryEvent(
                $messageId,
                'sent',
                $result
            );

            return [
                'success' => true,
                'status' => 'sent',
            ];
        } catch (\Throwable $exception) {
            $failed = $attempts >= $message->max_attempts;

            DB::table('notifications.messages')
                ->where('id', $messageId)
                ->update([
                    'status' => $failed ? 'failed' : 'pending',
                    'last_error' => $exception->getMessage(),
                    'failed_at' => $failed ? now() : null,
                    'scheduled_at' => $failed
                        ? null
                        : now()->addMinutes(
                            config(
                                'communications.retry_minutes'
                            )
                        ),
                    'updated_at' => now(),
                ]);

            $this->deliveryEvent(
                $messageId,
                $failed ? 'failed' : 'retry_scheduled',
                [
                    'error' => $exception->getMessage(),
                    'attempt' => $attempts,
                ]
            );

            throw $exception;
        }
    }

    public function unreadCount(string $userId): int
    {
        return DB::table(
            'notifications.in_app_notifications'
        )
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    private function createInApp(
        string $eventCode,
        array $recipient,
        array $variables
    ): string {
        $userId = $recipient['user_id'] ?? null;

        if (! $userId) {
            throw new RuntimeException(
                'In-app notifications require a user ID.'
            );
        }

        $template = DB::table('notifications.templates')
            ->where('code', $eventCode)
            ->where('active', true)
            ->first();

        if (! $template) {
            throw new RuntimeException(
                "No notification template found for {$eventCode}."
            );
        }

        $id = (string) Str::uuid();

        DB::table(
            'notifications.in_app_notifications'
        )->insert([
            'id' => $id,
            'user_id' => $userId,
            'event_code' => $eventCode,
            'title' => $this->render(
                $template->subject ?: $template->name,
                $variables
            ),
            'body' => $this->render(
                $template->body,
                $variables
            ),
            'action_url' => $variables['action_url'] ?? null,
            'priority' => $variables['priority'] ?? 'normal',
            'metadata' => json_encode($variables),
            'created_at' => now(),
        ]);

        return $id;
    }

    private function channelEnabled(
        ?string $userId,
        string $eventCode,
        string $channel
    ): bool {
        if (! $userId) {
            return $channel !== 'in_app';
        }

        $preference = DB::table(
            'notifications.user_preferences'
        )
            ->where('user_id', $userId)
            ->where('event_code', $eventCode)
            ->first();

        if (! $preference) {
            return in_array(
                $channel,
                config('communications.default_channels'),
                true
            );
        }

        $column = match ($channel) {
            'email' => 'email_enabled',
            'sms' => 'sms_enabled',
            'whatsapp' => 'whatsapp_enabled',
            'push' => 'push_enabled',
            'in_app' => 'in_app_enabled',
            default => null,
        };

        return $column
            ? (bool) $preference->{$column}
            : false;
    }

    private function recipientForChannel(
        array $recipient,
        string $channel
    ): string {
        return match ($channel) {
            'email' => $recipient['email'] ?? '',
            'sms', 'whatsapp' => $recipient['phone'] ?? '',
            'push' => $recipient['device_token'] ?? '',
            default => '',
        };
    }

    private function render(
        ?string $template,
        array $variables
    ): ?string {
        if ($template === null) {
            return null;
        }

        foreach ($variables as $key => $value) {
            $template = str_replace(
                '{{'.$key.'}}',
                (string) $value,
                $template
            );
        }

        return $template;
    }

    private function channel(string $channel): CommunicationChannel
    {
        return new LogChannel($channel);
    }

    private function deliveryEvent(
        string $messageId,
        string $eventType,
        array $payload
    ): void {
        DB::table('notifications.delivery_events')
            ->insert([
                'id' => (string) Str::uuid(),
                'message_id' => $messageId,
                'event_type' => $eventType,
                'payload' => json_encode($payload),
                'created_at' => now(),
            ]);
    }
}
