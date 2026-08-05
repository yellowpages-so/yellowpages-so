<?php

namespace App\Domain\Cms\Infrastructure;

use App\Domain\Cms\Contracts\PageRepository;
use App\Domain\Cms\DTO\CreatePageData;
use App\Domain\Cms\DTO\UpdatePageData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabasePageRepository implements PageRepository
{
    public function create(
        User $user,
        CreatePageData $data
    ): string {
        $id = (string) Str::uuid();

        DB::table('cms.pages')->insert([
            'id' => $id,
            'title' => $data->title,
            'slug' => $data->slug,
            'status' => $data->status,
            'body' => json_encode([
                'content' => $data->content,
                'blocks' => $data->blocks,
            ]),
            'seo_title' => $data->seo['title']
                ?? $data->seo['meta_title']
                ?? null,
            'seo_description' => $data->seo['description']
                ?? $data->seo['meta_description']
                ?? null,
            'published_at' => $data->status === 'published'
                ? now()
                : null,
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    public function update(
        string $id,
        UpdatePageData $data
    ): bool {
        $attributes = $data->attributes;
        $attributes['updated_at'] = now();

        return DB::table('cms.pages')
            ->where('id', $id)
            ->update($attributes) > 0;
    }

    public function delete(
        string $id
    ): bool {
        return DB::table('cms.pages')
            ->where('id', $id)
            ->delete() > 0;
    }

    public function find(
        string $id
    ): ?object {
        return DB::table('cms.pages')
            ->where('id', $id)
            ->first();
    }

    public function findBySlug(
        string $slug,
        string $locale = 'en'
    ): ?object {
        return DB::table('cms.pages')
            ->where('slug', $slug)
            ->first();
    }
}
