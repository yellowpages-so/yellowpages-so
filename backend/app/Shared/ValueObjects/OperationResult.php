<?php

namespace App\Shared\ValueObjects;

class OperationResult
{
    private function __construct(
        public readonly bool $successful,
        public readonly mixed $data = null,
        public readonly ?string $message = null
    ) {}

    public static function success(
        mixed $data = null,
        ?string $message = null
    ): self {
        return new self(
            successful: true,
            data: $data,
            message: $message,
        );
    }

    public static function failure(
        string $message,
        mixed $data = null
    ): self {
        return new self(
            successful: false,
            data: $data,
            message: $message,
        );
    }
}
