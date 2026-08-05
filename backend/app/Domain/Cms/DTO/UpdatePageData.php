<?php

namespace App\Domain\Cms\DTO;

final readonly class UpdatePageData
{
    public function __construct(
        public array $attributes,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            attributes: $data,
        );
    }
}
