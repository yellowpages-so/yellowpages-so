<?php

namespace App\Services\Communications;

use App\Contracts\CommunicationChannel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogChannel implements CommunicationChannel
{
    public function __construct(
        private readonly string $channel
    ) {}

    public function send(array $message): array
    {
        Log::info('Communication message', [
            'channel' => $this->channel,
            'recipient' => $message['recipient'] ?? null,
            'subject' => $message['subject'] ?? null,
            'body' => $message['body'] ?? null,
        ]);

        return [
            'success' => true,
            'provider' => 'log',
            'provider_message_id' => (string) Str::uuid(),
        ];
    }
}
