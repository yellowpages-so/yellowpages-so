<?php

namespace App\Shared\Contracts;

use App\Shared\Support\AuditContext;

interface AuditLogger
{
    public function record(
        string $event,
        AuditContext $context,
        array $payload = []
    ): void;
}
