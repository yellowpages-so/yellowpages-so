<?php

namespace App\Shared\ValueObjects;

use InvalidArgumentException;

class Pagination
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 20
    ) {
        if ($page < 1) {
            throw new InvalidArgumentException(
                'Page must be at least 1.'
            );
        }

        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException(
                'Per-page value must be between 1 and 100.'
            );
        }
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
