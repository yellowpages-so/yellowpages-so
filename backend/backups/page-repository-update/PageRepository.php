<?php

namespace App\Domain\Cms\Contracts;

use App\Models\User;

interface PageRepository
{
    public function create(
        User $user,
        array $attributes
    ): string;

    public function update(
        string $id,
        array $attributes
    ): bool;

    public function delete(
        string $id
    ): void;

    public function find(
        string $id
    ): ?object;

    public function findBySlug(
        string $slug,
        string $locale = 'en'
    ): ?object;
}
