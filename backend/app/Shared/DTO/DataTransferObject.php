<?php

namespace App\Shared\DTO;

interface DataTransferObject
{
    public static function fromArray(array $data): static;

    public function toArray(): array;
}
