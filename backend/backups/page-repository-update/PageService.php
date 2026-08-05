<?php

namespace App\Services\Cms;

use App\Shared\Exceptions\ResourceNotFoundException;
use App\Domain\Cms\Contracts\PageRepository;
use App\Models\User;
use App\Shared\Contracts\AuditLogger;
use App\Shared\Contracts\TransactionManager;
use App\Shared\Support\AuditContext;
use Illuminate\Support\Facades\DB;
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
        array $data
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
        array $data
    ): void {
        $page = DB::table('cms.pages')
            ->where('id', $pageId)
            ->first();

        if (! $page) {
            throw new RuntimeException('Page not found.');
        }

        $status = $data['status'] ?? $page->status;

        $updated = DB::table('cms.pages')
            ->where('id', $pageId)
            ->update([
                'slug' => $data['slug'] ?? $page->slug,
                'title' => $data['title'] ?? $page->title,
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
                'seo_title' => $data['seo']['title']
                    ?? $data['seo']['meta_title']
                    ?? $page->seo_title,
                'seo_description' => $data['seo']['description']
                    ?? $data['seo']['meta_description']
                    ?? $page->seo_description,
                'published_at' => $status === 'published'
                    ? ($page->published_at ?? now())
                    : null,
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            throw new RuntimeException(
                'Page could not be updated.'
            );
        }
    }

    public function delete(string $pageId): void
    {
        $deleted = DB::table('cms.pages')
            ->where('id', $pageId)
            ->delete();

        if ($deleted === 0) {
            throw new RuntimeException('Page not found.');
        }
    }

    public function publicBySlug(
        string $slug,
        string $locale = 'en'
    ): object {
        $page = DB::table('cms.pages')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->first();

        if (! $page) {
            throw new RuntimeException('Page not found.');
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
