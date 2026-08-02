<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SmartSearchTest extends TestCase
{
    public function test_search_endpoint_returns_success_with_database_fallback(): void
    {
        config([
            'search.driver' => 'database',
        ]);

        $this->getJson('/api/v1/search?q=insurance')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.source', 'database');

        $this->assertGreaterThanOrEqual(
            1,
            DB::table('analytics.search_events')->count()
        );
    }

    public function test_suggestions_require_two_characters_for_results(): void
    {
        config([
            'search.driver' => 'database',
        ]);

        $this->getJson('/api/v1/search/suggestions?q=a')
            ->assertOk()
            ->assertJsonPath('data', []);
    }
}
