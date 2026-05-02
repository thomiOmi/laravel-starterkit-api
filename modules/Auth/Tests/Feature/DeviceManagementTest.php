<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Tests\TestCase;

class DeviceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_devices(): void
    {
        $tenant = Tenant::create(['id' => 'test-tenant']);
        tenancy()->initialize($tenant);

        $user = User::factory()->create();

        $user->createToken('Device 1', [], null, $tenant->id);
        $user->createToken('Device 2', [], null, $tenant->id);

        $response = $this->getJson('/api/v1/auth/devices', [
            'X-Tenant' => $tenant->id,
            'Authorization' => 'Bearer '.$user->createToken('Current Device', [], null, $tenant->id)->plainTextToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_logout_other_devices(): void
    {
        $tenant = Tenant::create(['id' => 'test-tenant']);
        tenancy()->initialize($tenant);

        $user = User::factory()->create();

        $user->createToken('Other Device', [], null, $tenant->id);
        $currentToken = $user->createToken('Current Device', [], null, $tenant->id)->plainTextToken;

        $response = $this->postJson('/api/v1/auth/devices/logout-others', [], [
            'X-Tenant' => $tenant->id,
            'Authorization' => 'Bearer '.$currentToken,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(1, $user->tokens()->count());
    }
}
