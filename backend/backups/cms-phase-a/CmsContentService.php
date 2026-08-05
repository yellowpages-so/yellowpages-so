<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class CmsContentService
{
    public function createPage(User $user, array $data): string
    {
        $id = (string) Str::uuid();

        DB::transaction(function () use ($user, $data, $id): void {
            $payload = [
                'id' => $id,
                'slug' => $data['slug'],
                'title' => $data['title'],
                'status' => $data['status'] ?? 'draft',
                'published_at' => ($data['status'] ?? 'draft') === 'published'
                    ? now()
                    : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('cms.pages', 'body')) {
                $payload['body'] = json_encode([
                    'content' => $data['content'] ?? '',
                    'blocks' => $data['blocks'] ?? [],
                ]);
            }

            if (Schema::hasColumn('cms.pages', 'content')) {
                $payload['content'] = $data['content'] ?? null;
            }

            if (Schema::hasColumn('cms.pages', 'blocks')) {
                $payload['blocks'] = json_encode(
                    $data['blocks'] ?? []
                );
            }

            if (Schema::hasColumn('cms.pages', 'created_by')) {
                $payload['created_by'] = $user->id;
            }

            if (Schema::hasColumn('cms.pages', 'author_id')) {
                $payload['author_id'] = $user->id;
            }

            if (Schema::hasColumn('cms.pages', 'template')) {
                $payload['template'] = $data['template'] ?? 'default';
            }

            if (Schema::hasColumn('cms.pages', 'excerpt')) {
                $payload['excerpt'] = $data['excerpt'] ?? null;
            }

            if (Schema::hasColumn('cms.pages', 'locale')) {
                $payload['locale'] = $data['locale'] ?? 'en';
            }

            if (Schema::hasColumn('cms.pages', 'is_homepage')) {
                $payload['is_homepage'] =
                    $data['is_homepage'] ?? false;
            }

            if (Schema::hasColumn('cms.pages', 'scheduled_at')) {
                $payload['scheduled_at'] =
                    $data['scheduled_at'] ?? null;
            }

            if (Schema::hasColumn('cms.pages', 'seo_title')) {
                $payload['seo_title'] =
                    $data['seo']['meta_title']
                    ?? $data['seo']['title']
                    ?? null;
            }

            if (
                Schema::hasColumn(
                    'cms.pages',
                    'seo_description'
                )
            ) {
                $payload['seo_description'] =
                    $data['seo']['meta_description']
                    ?? $data['seo']['description']
                    ?? null;
            }

            DB::table('cms.pages')->insert($payload);

            if (Schema::hasTable('cms.seo_metadata')) {
                $this->saveSeo(
                    'page',
                    $id,
                    $data['seo'] ?? []
                );
            }

            if (Schema::hasTable('cms.revisions')) {
                $this->revision(
                    $user,
                    'page',
                    $id,
                    $data['title'],
                    $data['content'] ?? null,
                    $data
                );
            }
        });

        return $id;
    }

    public function createPost(User $user, array $data): string
    {
        if (! Schema::hasTable('cms.posts')) {
            throw new RuntimeException(
                'CMS posts table does not exist.'
            );
        }

        $id = (string) Str::uuid();

        DB::transaction(function () use ($user, $data, $id): void {
            $payload = [
                'id' => $id,
                'title' => $data['title'],
                'slug' => $data['slug'],
                'status' => $data['status'] ?? 'draft',
                'published_at' => ($data['status'] ?? 'draft')
                    === 'published'
                    ? now()
                    : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('cms.posts', 'created_by')) {
                $payload['created_by'] = $user->id;
            }

            if (Schema::hasColumn('cms.posts', 'author_id')) {
                $payload['author_id'] = $user->id;
            }

            if (Schema::hasColumn('cms.posts', 'body')) {
                $payload['body'] = json_encode([
                    'content' => $data['content'] ?? '',
                ]);
            }

            if (Schema::hasColumn('cms.posts', 'content')) {
                $payload['content'] = $data['content'] ?? null;
            }

            if (Schema::hasColumn('cms.posts', 'excerpt')) {
                $payload['excerpt'] = $data['excerpt'] ?? null;
            }

            if (Schema::hasColumn('cms.posts', 'locale')) {
                $payload['locale'] = $data['locale'] ?? 'en';
            }

            if (
                Schema::hasColumn(
                    'cms.posts',
                    'featured_media_id'
                )
            ) {
                $payload['featured_media_id'] =
                    $data['featured_media_id'] ?? null;
            }

            if (Schema::hasColumn('cms.posts', 'featured')) {
                $payload['featured'] =
                    $data['featured'] ?? false;
            }

            if (Schema::hasColumn('cms.posts', 'scheduled_at')) {
                $payload['scheduled_at'] =
                    $data['scheduled_at'] ?? null;
            }

            DB::table('cms.posts')->insert($payload);

            if (Schema::hasTable('cms.post_categories')) {
                foreach (
                    $data['category_ids'] ?? [] as $categoryId
                ) {
                    DB::table('cms.post_categories')->insert([
                        'post_id' => $id,
                        'category_id' => $categoryId,
                    ]);
                }
            }

            if (Schema::hasTable('cms.post_tags')) {
                foreach ($data['tag_ids'] ?? [] as $tagId) {
                    DB::table('cms.post_tags')->insert([
                        'post_id' => $id,
                        'tag_id' => $tagId,
                    ]);
                }
            }

            if (Schema::hasTable('cms.seo_metadata')) {
                $this->saveSeo(
                    'post',
                    $id,
                    $data['seo'] ?? []
                );
            }

            if (Schema::hasTable('cms.revisions')) {
                $this->revision(
                    $user,
                    'post',
                    $id,
                    $data['title'],
                    $data['content'] ?? null,
                    $data
                );
            }
        });

        return $id;
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
        $query = DB::table('cms.pages')
            ->where('slug', $slug)
            ->where('status', 'published');

        if (Schema::hasColumn('cms.pages', 'locale')) {
            $query->where('locale', $locale);
        }

        if (Schema::hasColumn('cms.pages', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $page = $query->first();

        if (! $page) {
            throw new RuntimeException('Page not found.');
        }

        if (Schema::hasTable('cms.seo_metadata')) {
            $page->seo = DB::table('cms.seo_metadata')
                ->where('content_type', 'page')
                ->where('content_id', $page->id)
                ->first();
        }

        return $page;
    }

    public function publicPost(
        string $slug,
        string $locale = 'en'
    ): object {
        if (! Schema::hasTable('cms.posts')) {
            throw new RuntimeException('Post not found.');
        }

        $query = DB::table('cms.posts')
            ->where('slug', $slug)
            ->where('status', 'published');

        if (Schema::hasColumn('cms.posts', 'locale')) {
            $query->where('locale', $locale);
        }

        if (Schema::hasColumn('cms.posts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $post = $query->first();

        if (! $post) {
            throw new RuntimeException('Post not found.');
        }

        if (Schema::hasTable('cms.seo_metadata')) {
            $post->seo = DB::table('cms.seo_metadata')
                ->where('content_type', 'post')
                ->where('content_id', $post->id)
                ->first();
        }

        return $post;
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
