<?php

namespace App\Shared\Support;

use Illuminate\Http\Request;

class AuditContext
{
    public function __construct(
        public readonly ?string $requestId,
        public readonly ?string $userId,
        public readonly ?string $businessId,
        public readonly ?string $ipAddress,
        public readonly string $occurredAt
    ) {}

    public static function fromRequest(
        Request $request,
        ?string $businessId = null
    ): self {
        return new self(
            requestId: $request->attributes->get('request_id'),
            userId: $request->user()?->id,
            businessId: $businessId,
            ipAddress: $request->ip(),
            occurredAt: now()->toIso8601String(),
        );
    }

    public function toArray(): array
    {
        return [
            'request_id' => $this->requestId,
            'user_id' => $this->userId,
            'business_id' => $this->businessId,
            'ip_address' => $this->ipAddress,
            'occurred_at' => $this->occurredAt,
        ];
    }
}
