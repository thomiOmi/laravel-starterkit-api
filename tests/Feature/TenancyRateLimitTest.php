<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenant\Models\Tenant;
use Tests\TestCase;

class TenancyRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_rate_limit_is_tenant_aware(): void
    {
        // 1. Create tenant with custom rate limit
        $tenant = Tenant::create([
            'id' => 'premium-tenant',
            'rate_limit' => 2, // very low for testing
        ]);

        // 2. Make requests and check headers
        // Request 1
        $this->getJson('/api/v1/tenants', ['X-Tenant' => 'premium-tenant'])
            ->assertHeader('X-RateLimit-Limit', '2')
            ->assertHeader('X-RateLimit-Remaining', '1');

        // Request 2
        $this->getJson('/api/v1/tenants', ['X-Tenant' => 'premium-tenant'])
            ->assertHeader('X-RateLimit-Remaining', '0');

        // Request 3 -> Should be throttled
        $this->getJson('/api/v1/tenants', ['X-Tenant' => 'premium-tenant'])
            ->assertStatus(429);
    }
}
