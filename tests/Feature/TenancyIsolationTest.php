<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Tests\TestCase;

class TenancyIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenants
        $tenant1 = Tenant::create(['id' => 'tenant1']);
        $tenant2 = Tenant::create(['id' => 'tenant2']);

        // Create users for tenant1
        tenancy()->initialize($tenant1);
        User::create([
            'name' => 'User Tenant 1',
            'email' => 'user1@tenant1.com',
            'password' => 'password',
        ]);
        tenancy()->end();

        // Create users for tenant2
        tenancy()->initialize($tenant2);
        User::create([
            'name' => 'User Tenant 2',
            'email' => 'user2@tenant2.com',
            'password' => 'password',
        ]);
        tenancy()->end();
    }

    public function test_data_is_isolated_between_tenants(): void
    {
        // Check tenant1
        $response1 = $this->getJson('/api/v1/auth/me', [
            'X-Tenant' => 'tenant1',
            'Authorization' => 'Bearer '.$this->getAuthToken('user1@tenant1.com', 'tenant1'),
        ]);
        $response1->assertStatus(200);
        $response1->assertJsonPath('data.email', 'user1@tenant1.com');

        // Check tenant2
        $response2 = $this->getJson('/api/v1/auth/me', [
            'X-Tenant' => 'tenant2',
            'Authorization' => 'Bearer '.$this->getAuthToken('user2@tenant2.com', 'tenant2'),
        ]);
        $response2->assertStatus(200);
        $response2->assertJsonPath('data.email', 'user2@tenant2.com');

        // Tenant 1 should NOT see user from Tenant 2
        tenancy()->initialize(Tenant::find('tenant1'));
        $this->assertEquals(1, User::count());
        $this->assertFalse(User::where('email', 'user2@tenant2.com')->exists());
        tenancy()->end();
    }

    protected function getAuthToken(string $email, string $tenantId): string
    {
        $tenant = Tenant::find($tenantId);
        tenancy()->initialize($tenant);
        $user = User::where('email', $email)->first();
        $token = $user->createToken('test-token')->plainTextToken;
        tenancy()->end();

        return $token;
    }
}
