<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Models\User;
use Tests\TestCase;

class DeviceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_devices(): void
    {
        $user = User::factory()->create();

        $user->createToken('Device 1');
        $user->createToken('Device 2');

        $currentToken = $user->createToken('Current Device');

        $response = $this->getJson('/api/v1/auth/devices', [
            'Authorization' => 'Bearer '.$currentToken->plainTextToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_logout_other_devices(): void
    {
        $user = User::factory()->create();

        $user->createToken('Other Device');
        $currentTokenResult = $user->createToken('Current Device');
        $currentToken = $currentTokenResult->plainTextToken;

        $response = $this->postJson('/api/v1/auth/devices/logout-others', [], [
            'Authorization' => 'Bearer '.$currentToken,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(1, $user->tokens()->count());
    }
}
