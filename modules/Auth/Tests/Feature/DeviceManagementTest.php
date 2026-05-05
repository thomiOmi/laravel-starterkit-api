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

        $user->createToken('Device 1')->accessToken->forceFill(['tenant_id' => $tenant->id])->save();
        $user->createToken('Device 2')->accessToken->forceFill(['tenant_id' => $tenant->id])->save();

        $currentToken = $user->createToken('Current Device');
        $currentToken->accessToken->forceFill(['tenant_id' => $tenant->id])->save();

        $response = $this->getJson('/api/v1/auth/devices', [
            'X-Tenant' => $tenant->id,
            'Authorization' => 'Bearer '.$currentToken->plainTextToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_logout_other_devices(): void
    {
        $tenant = Tenant::create(['id' => 'test-tenant']);
        tenancy()->initialize($tenant);

        $user = User::factory()->create();

        $user->createToken('Other Device')->accessToken->forceFill(['tenant_id' => $tenant->id])->save();
        $currentTokenResult = $user->createToken('Current Device');
        $currentTokenResult->accessToken->forceFill(['tenant_id' => $tenant->id])->save();
        $currentToken = $currentTokenResult->plainTextToken;

        $response = $this->postJson('/api/v1/auth/devices/logout-others', [], [
            'X-Tenant' => $tenant->id,
            'Authorization' => 'Bearer '.$currentToken,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(1, $user->tokens()->count());
    }
}
