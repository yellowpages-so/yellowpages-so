<?php

namespace App\Shared\Infrastructure;

use App\Shared\Contracts\AuditLogger;
use App\Shared\Support\AuditContext;
use Illuminate\Support\Facades\Log;

class LaravelAuditLogger implements AuditLogger
{
    public function record(
        string $event,
        AuditContext $context,
        array $payload = []
    ): void {
        Log::info(
            'audit.'.$event,
            [
                ...$context->toArray(),
                'payload' => $payload,
            ],
        );
    }
}
