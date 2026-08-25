<?php

namespace App\Domain\Directory\Contracts;

use App\Domain\Directory\DTO\CreateBusinessData;
use App\Domain\Directory\DTO\UpdateBusinessData;
use App\Models\Business;
use App\Models\User;

interface BusinessRepository
{
    public function create(
        User $owner,
        CreateBusinessData $data
    ): Business;

    public function update(
        Business $business,
        UpdateBusinessData $data
    ): Business;

    public function archive(
        Business $business
    ): void;

    public function find(
        string $publicId
    ): ?Business;
}
