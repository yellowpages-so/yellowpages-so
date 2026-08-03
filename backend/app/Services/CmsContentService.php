<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CmsContentService
{
    public function createPage(User $user, array $data): string
    {
        $id = (string) Str::uuid();

        DB::transaction(function () use ($user, $data, $id): void {
            DB::table('cms.pages')->insert([
                'id' => $id,
                'author_id' => $user->id,
                'title' => $data['title'],
                'slug' => $data['slug'],
                'template' => $data['template'] ?? 'default',
                'status' => $data['status'] ?? 'draft',
                'excerpt' => $data['excerpt'] ?? null,
                'content' => $data['content'] ?? null,
                'blocks' => json_encode($data['blocks'] ?? []),
                'locale' => $data['locale'] ?? 'en',
                'is_homepage' => $data['is_homepage'] ?? false,
                'published_at' => ($data['status'] ?? 'draft') === 'published'
                    ? now()
                    : null,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->saveSeo('page', $id, $data['seo'] ?? []);
            $this->revision($user, 'page', $id, $data['title'], $data['content'] ?? null, $data);
        });

        return $id;
    }

    public function createPost(User $user, array $data): string
    {
        $id = (string) Str::uuid();

        DB::transaction(function () use ($user, $data, $id): void {
            DB::table('cms.posts')->insert([
                'id' => $id,
                'author_id' => $user->id,
                'title' => $data['title'],
                'slug' => $data['slug'],
                'excerpt' => $data['excerpt'] ?? null,
                'content' => $data['content'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'locale' => $data['locale'] ?? 'en',
                'featured_media_id' => $data['featured_media_id'] ?? null,
                'featured' => $data['featured'] ?? false,
                'published_at' => ($data['status'] ?? 'draft') === 'published'
                    ? now()
                    : null,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($data['category_ids'] ?? [] as $categoryId) {
                DB::table('cms.post_categories')->insert([
                    'post_id' => $id,
                    'category_id' => $categoryId,
                ]);
            }

            foreach ($data['tag_ids'] ?? [] as $tagId) {
                DB::table('cms.post_tags')->insert([
                    'post_id' => $id,
                    'tag_id' => $tagId,
                ]);
            }

            $this->saveSeo('post', $id, $data['seo'] ?? []);
            $this->revision($user, 'post', $id, $data['title'], $data['content'] ?? null, $data);
        });

        return $id;
    }

    public function publishScheduled(): int
    {
        $count = 0;

        foreach (['cms.pages', 'cms.posts'] as $table) {
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

    public function publicPage(string $slug, string $locale): object
    {
        $page = DB::table('cms.pages')
            ->where('slug', $slug)
            ->where('locale', $locale)
            ->where('status', 'published')
            ->whereNull('deleted_at')
            ->first();

        if (! $page) {
            throw new RuntimeException('Page not found.');
        }

        $page->seo = DB::table('cms.seo_metadata')
            ->where('content_type', 'page')
            ->where('content_id', $page->id)
            ->first();

        return $page;
    }

    public function publicPost(string $slug, string $locale): object
    {
        $post = DB::table('cms.posts')
            ->where('slug', $slug)
            ->where('locale', $locale)
            ->where('status', 'published')
            ->whereNull('deleted_at')
            ->first();

        if (! $post) {
            throw new RuntimeException('Post not found.');
        }

        $post->seo = DB::table('cms.seo_metadata')
            ->where('content_type', 'post')
            ->where('content_id', $post->id)
            ->first();

        return $post;
    }

    private function saveSeo(
        string $contentType,
        string $contentId,
        array $seo
    ): void {
        DB::table('cms.seo_metadata')->updateOrInsert(
            [
                'content_type' => $contentType,
                'content_id' => $contentId,
            ],
            [
                'id' => DB::table('cms.seo_metadata')
                    ->where('content_type', $contentType)
                    ->where('content_id', $contentId)
                    ->value('id') ?? (string) Str::uuid(),
                'meta_title' => $seo['meta_title'] ?? null,
                'meta_description' => $seo['meta_description'] ?? null,
                'canonical_url' => $seo['canonical_url'] ?? null,
                'robots' => $seo['robots'] ?? 'index,follow',
                'og_title' => $seo['og_title'] ?? null,
                'og_description' => $seo['og_description'] ?? null,
                'og_image' => $seo['og_image'] ?? null,
                'twitter_card' => $seo['twitter_card'] ?? 'summary_large_image',
                'structured_data' => json_encode($seo['structured_data'] ?? []),
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
