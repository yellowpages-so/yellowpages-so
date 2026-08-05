<?php

namespace App\Services;

use App\Domain\Cms\DTO\CreatePageData;
use App\Models\User;
use App\Services\Cms\PageService;
use App\Services\Cms\PostService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CmsContentService
{
    public function __construct(
        private readonly PageService $pages,
        private readonly PostService $posts
    ) {}

    public function createPage(
        User $user,
        array $data
    ): string {
        return DB::transaction(
            fn (): string => $this->pages->create(
                $user,
                CreatePageData::fromArray($data)
            )
        );
    }

    public function createPost(
        User $user,
        array $data
    ): string {
        return $this->posts->create(
            $user,
            $data
        );
    }

    public function publishScheduled(): int
    {
        $count = 0;

        foreach (['cms.pages', 'cms.posts'] as $table) {
            if (
                ! Schema::hasTable($table)
                || ! Schema::hasColumn($table, 'scheduled_at')
            ) {
                continue;
            }

            $count += DB::table($table)
                ->where('status', 'scheduled')
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '<=', now())
                ->update([
                    'status' => 'published',
                    'published_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        return $count;
    }

    public function publicPage(
        string $slug,
        string $locale = 'en'
    ): object {
        return $this->pages->publicBySlug(
            $slug,
            $locale
        );
    }

    public function publicPost(
        string $slug,
        string $locale = 'en'
    ): object {
        return $this->posts->publicBySlug(
            $slug,
            $locale
        );
    }

    private function saveSeo(
        string $contentType,
        string $contentId,
        array $seo
    ): void {
        if (! Schema::hasTable('cms.seo_metadata')) {
            return;
        }

        $existingId = DB::table('cms.seo_metadata')
            ->where('content_type', $contentType)
            ->where('content_id', $contentId)
            ->value('id');

        DB::table('cms.seo_metadata')->updateOrInsert(
            [
                'content_type' => $contentType,
                'content_id' => $contentId,
            ],
            [
                'id' => $existingId ?? (string) Str::uuid(),
                'meta_title' => $seo['meta_title']
                    ?? $seo['title']
                    ?? null,
                'meta_description' => $seo['meta_description']
                    ?? $seo['description']
                    ?? null,
                'canonical_url' => $seo['canonical_url'] ?? null,
                'robots' => $seo['robots'] ?? 'index,follow',
                'og_title' => $seo['og_title'] ?? null,
                'og_description' => $seo['og_description'] ?? null,
                'og_image' => $seo['og_image'] ?? null,
                'twitter_card' => $seo['twitter_card']
                    ?? 'summary_large_image',
                'structured_data' => json_encode(
                    $seo['structured_data'] ?? []
                ),
                'updated_at' => now(),
            ]
        );
    }

    private function revision(
        User $user,
        string $type,
        string $id,
        string $title,
        ?string $content,
        array $payload
    ): void {
        if (! Schema::hasTable('cms.revisions')) {
            return;
        }

        DB::table('cms.revisions')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'content_type' => $type,
            'content_id' => $id,
            'title' => $title,
            'content' => $content,
            'payload' => json_encode($payload),
            'created_at' => now(),
        ]);
    }
}
