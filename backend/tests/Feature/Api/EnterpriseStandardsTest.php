<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class EnterpriseStandardsTest extends TestCase
{
    public function test_liveness_endpoint_works(): void
    {
        $this->getJson('/api/v1/platform/live')
            ->assertOk()
            ->assertJsonPath('status', 'healthy');
    }

    public function test_request_id_is_returned(): void
    {
        $this->withHeader('X-Request-ID', 'enterprise-test')
            ->getJson('/api/v1/platform/live')
            ->assertHeader('X-Request-ID', 'enterprise-test');
    }
}
