<?php

namespace App\Domain\Cms\Infrastructure;

use App\Domain\Cms\Contracts\PageRepository;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabasePageRepository implements PageRepository
{
    public function create(
        User $user,
        array $attributes
    ): string {
        $id = (string) Str::uuid();

        DB::table('cms.pages')->insert([
            'id' => $id,
            'title' => $attributes['title'],
            'slug' => $attributes['slug'],
            'status' => $attributes['status'] ?? 'draft',

            'body' => json_encode([
                'content' => $attributes['content'] ?? '',
                'blocks' => $attributes['blocks'] ?? [],
            ]),
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    public function update(
        string $id,
        array $attributes
    ): bool {
        DB::table('cms.pages')
            ->where('id', $id)
            ->update(
                array_merge(
                    $attributes,
                    [
                        'updated_at' => now(),
                    ]
                )
            );
    }

    public function delete(
        string $id
    ): void {
        DB::table('cms.pages')
            ->where('id', $id)
            ->delete();
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
