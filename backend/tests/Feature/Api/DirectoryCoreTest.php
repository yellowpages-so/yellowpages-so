<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DirectoryCoreTest extends TestCase
{
    public function test_taxonomy_files_validate(): void
    {
        $this->assertSame(0, Artisan::call('directory:taxonomy-import', ['--path' => '../data/directory', '--dry-run' => true]));
    }

    public function test_public_categories_endpoint_works(): void
    {
        $this->getJson('/api/v1/categories')->assertOk();
    }

    public function test_public_search_endpoint_works(): void
    {
        $this->getJson('/api/v1/directory/search?q=insurance')->assertOk()->assertJsonPath('success', true);
    }
}
