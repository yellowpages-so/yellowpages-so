<?php

namespace App\Domain\Cms\Contracts;

use App\Domain\Cms\DTO\CreatePageData;
use App\Domain\Cms\DTO\UpdatePageData;
use App\Models\User;

interface PageRepository
{
    public function create(
        User $user,
        CreatePageData $data
    ): string;

    public function update(
        string $id,
        UpdatePageData $data
    ): bool;

    public function delete(
        string $id
    ): bool;

    public function find(
        string $id
    ): ?object;

    public function findBySlug(
        string $slug,
        string $locale = 'en'
    ): ?object;
}
