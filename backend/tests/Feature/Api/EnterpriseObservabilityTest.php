<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class EnterpriseObservabilityTest extends TestCase
{
    public function test_metrics_endpoint_returns_prometheus_data(): void
    {
        $this->get('/api/metrics')
            ->assertOk()
            ->assertSee('yellowpages_application_up 1');
    }
}
