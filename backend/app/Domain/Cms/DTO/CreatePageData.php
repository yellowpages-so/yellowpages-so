<?php

namespace App\Domain\Cms\DTO;

final readonly class CreatePageData
{
    public function __construct(
        public string $title,
        public string $slug,
        public string $content = '',
        public array $blocks = [],
        public string $status = 'draft',
        public ?array $seo = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            slug: $data['slug'],
            content: $data['content'] ?? '',
            blocks: $data['blocks'] ?? [],
            status: $data['status'] ?? 'draft',
            seo: $data['seo'] ?? null,
        );
    }
}
