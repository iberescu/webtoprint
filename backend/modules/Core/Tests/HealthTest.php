<?php

namespace Modules\Core\Tests;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_endpoint_returns_status(): void
    {
        // Endpoint always responds (200 healthy or 503 degraded). Both shapes match.
        $this->getJson('/api/v1/health')
            ->assertJsonStructure(['status', 'name', 'version', 'checks']);
    }
}
