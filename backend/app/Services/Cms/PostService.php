<?php

namespace App\Services\Cms;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PostService
{
    public function create(User $user, array $data): string
    {
        $id = (string) Str::uuid();
        $status = $data['status'] ?? 'draft';

        DB::transaction(function () use (
            $user,
            $data,
            $id,
            $status
        ): void {
            DB::table('cms.posts')->insert([
                'id' => $id,
                'author_id' => $user->id,
                'title' => $data['title'],
                'slug' => $data['slug'],
                'excerpt' => $data['excerpt'] ?? null,
                'content' => $data['content'] ?? null,
                'status' => $status,
                'locale' => $data['locale'] ?? 'en',
                'featured_media_id' => $data['featured_media_id'] ?? null,
                'featured' => $data['featured'] ?? false,
                'published_at' => $status === 'published'
                    ? now()
                    : null,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->syncCategories(
                $id,
                $data['category_ids'] ?? []
            );

            $this->syncTags(
                $id,
                $data['tag_ids'] ?? []
            );
        });

        return $id;
    }

    public function update(
        User $user,
        string $postId,
        array $data
    ): void {
        DB::transaction(function () use (

            $postId,
            $data
        ): void {
            $post = DB::table('cms.posts')
                ->where('id', $postId)
                ->first();

            if (! $post) {
                throw new RuntimeException(
                    'Post not found.'
                );
            }

            $status = $data['status']
                ?? $post->status;

            DB::table('cms.posts')
                ->where('id', $postId)
                ->update([
                    'title' => $data['title']
                        ?? $post->title,
                    'slug' => $data['slug']
                        ?? $post->slug,
                    'excerpt' => array_key_exists(
                        'excerpt',
                        $data
                    )
                        ? $data['excerpt']
                        : $post->excerpt,
                    'content' => array_key_exists(
                        'content',
                        $data
                    )
                        ? $data['content']
                        : $post->content,
                    'status' => $status,
                    'locale' => $data['locale']
                        ?? $post->locale,
                    'featured_media_id' => array_key_exists(
                        'featured_media_id',
                        $data
                    )
                            ? $data['featured_media_id']
                            : $post->featured_media_id,
                    'featured' => $data['featured']
                        ?? $post->featured,
                    'published_at' => $status === 'published'
                            ? (
                                $post->published_at
                                ?? now()
                            )
                            : null,
                    'scheduled_at' => array_key_exists(
                        'scheduled_at',
                        $data
                    )
                            ? $data['scheduled_at']
                            : $post->scheduled_at,
                    'updated_at' => now(),
                ]);

            if (array_key_exists(
                'category_ids',
                $data
            )) {
                $this->syncCategories(
                    $postId,
                    $data['category_ids']
                );
            }

            if (array_key_exists('tag_ids', $data)) {
                $this->syncTags(
                    $postId,
                    $data['tag_ids']
                );
            }
        });
    }

    public function delete(string $postId): void
    {
        DB::transaction(function () use ($postId): void {
            $post = DB::table('cms.posts')
                ->where('id', $postId)
                ->first();

            if (! $post) {
                throw new RuntimeException(
                    'Post not found.'
                );
            }

            DB::table('cms.post_categories')
                ->where('post_id', $postId)
                ->delete();

            DB::table('cms.post_tags')
                ->where('post_id', $postId)
                ->delete();

            DB::table('cms.posts')
                ->where('id', $postId)
                ->delete();
        });
    }

    public function publicBySlug(
        string $slug,
        string $locale = 'en'
    ): object {
        $post = DB::table('cms.posts')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->where('locale', $locale)
            ->first();

        if (! $post) {
            throw new RuntimeException(
                'Post not found.'
            );
        }

        return $post;
    }

    private function syncCategories(
        string $postId,
        array $categoryIds
    ): void {
        DB::table('cms.post_categories')
            ->where('post_id', $postId)
            ->delete();

        foreach (array_unique($categoryIds) as $categoryId) {
            DB::table('cms.post_categories')->insert([
                'post_id' => $postId,
                'category_id' => $categoryId,
            ]);
        }
    }

    private function syncTags(
        string $postId,
        array $tagIds
    ): void {
        DB::table('cms.post_tags')
            ->where('post_id', $postId)
            ->delete();

        foreach (array_unique($tagIds) as $tagId) {
            DB::table('cms.post_tags')->insert([
                'post_id' => $postId,
                'tag_id' => $tagId,
            ]);
        }
    }
}
