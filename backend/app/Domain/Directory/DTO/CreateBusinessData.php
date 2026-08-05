<?php

namespace App\Domain\Directory\DTO;

use InvalidArgumentException;

final readonly class CreateBusinessData
{
    public function __construct(
        public string $tradingName,
        public array $attributes,
    ) {
        if ($tradingName === '') {
            throw new InvalidArgumentException(
                'Trading name is required.'
            );
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(
            tradingName: $data['trading_name'],
            attributes: $data,
        );
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}
