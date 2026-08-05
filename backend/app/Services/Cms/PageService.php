<?php

namespace App\Services\Cms;

use App\Domain\Cms\Contracts\PageRepository;
use App\Domain\Cms\DTO\CreatePageData;
use App\Domain\Cms\DTO\UpdatePageData;
use App\Models\User;
use App\Shared\Contracts\AuditLogger;
use App\Shared\Contracts\TransactionManager;
use App\Shared\Exceptions\ResourceNotFoundException;
use App\Shared\Support\AuditContext;
use RuntimeException;

class PageService
{
    public function __construct(
        private readonly PageRepository $pages,
        private readonly TransactionManager $transactions,
        private readonly AuditLogger $audit,
    ) {}

    public function create(
        User $user,
        CreatePageData $data
    ): string {
        return $this->transactions->run(
            function () use ($user, $data): string {
                $pageId = $this->pages->create(
                    $user,
                    $data
                );

                $this->audit->record(
                    'cms.page.created',
                    AuditContext::fromRequest(
                        request()
                    ),
                    [
                        'page_id' => $pageId,
                    ]
                );

                return $pageId;
            }
        );
    }

    public function update(
        User $user,
        string $pageId,
        UpdatePageData $data
    ): void {
        $this->transactions->run(
            function () use (
                $user,
                $pageId,
                $data
            ): void {
                $page = $this->pages->find(
                    $pageId
                );

                if (! $page) {
                    throw new ResourceNotFoundException(
                        'Page',
                        $pageId
                    );
                }

                $status = $attributes['status']
                    ?? $page->status;

                $updated = $this->pages->update(
                    $pageId,
                    [
                        'slug' => $attributes['slug']
                            ?? $page->slug,
                        'title' => $attributes['title']
                            ?? $page->title,
                        'body' => json_encode([
                            'content' => $data['content']
                                ?? $this->bodyValue(
                                    $page->body,
                                    'content',
                                    ''
                                ),
                            'blocks' => $data['blocks']
                                ?? $this->bodyValue(
                                    $page->body,
                                    'blocks',
                                    []
                                ),
                        ]),
                        'status' => $status,
                        'seo_title' => $attributes['seo']['title']
                            ?? $attributes['seo']['meta_title']
                            ?? $page->seo_title,
                        'seo_description' => $attributes['seo']['description']
                            ?? $attributes['seo']['meta_description']
                            ?? $page->seo_description,
                        'published_at' => $status === 'published'
                                ? (
                                    $page->published_at
                                    ?? now()
                                )
                                : null,
                    ]
                );

                if (! $updated) {
                    throw new RuntimeException(
                        'Page could not be updated.'
                    );
                }

                $this->audit->record(
                    'cms.page.updated',
                    AuditContext::fromRequest(
                        request()
                    ),
                    [
                        'page_id' => $pageId,
                        'updated_by' => $user->id,
                    ]
                );
            }
        );
    }

    public function delete(
        string $pageId
    ): void {
        $this->transactions->run(
            function () use ($pageId): void {
                $deleted = $this->pages->delete(
                    $pageId
                );

                if (! $deleted) {
                    throw new ResourceNotFoundException(
                        'Page',
                        $pageId
                    );
                }

                $this->audit->record(
                    'cms.page.deleted',
                    AuditContext::fromRequest(
                        request()
                    ),
                    [
                        'page_id' => $pageId,
                    ]
                );
            }
        );
    }

    public function publicBySlug(
        string $slug,
        string $locale = 'en'
    ): object {
        $page = $this->pages->findBySlug(
            $slug,
            $locale
        );

        if (! $page || $page->status !== 'published') {
            throw new ResourceNotFoundException(
                'Page',
                $slug
            );
        }

        return $page;
    }

    private function bodyValue(
        mixed $body,
        string $key,
        mixed $default
    ): mixed {
        if (is_string($body)) {
            $decoded = json_decode($body, true);

            if (is_array($decoded)) {
                return $decoded[$key] ?? $default;
            }
        }

        if (is_array($body)) {
            return $body[$key] ?? $default;
        }

        return $default;
    }
}
