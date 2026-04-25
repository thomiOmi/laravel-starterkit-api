<?php

declare(strict_types=1);

namespace Modules\AuditLog\Tests\Feature;

use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\Role\Models\Role;
use Modules\User\Models\User;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_it_logs_user_creation(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $user->id,
            'subject_type' => User::class,
            'description' => 'created',
        ]);
    }

    public function test_it_logs_user_update(): void
    {
        $user = User::factory()->create();

        $user->update(['name' => 'Updated Name']);

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $user->id,
            'subject_type' => User::class,
            'description' => 'updated',
        ]);
    }

    public function test_it_logs_role_creation(): void
    {
        $role = Role::create(['name' => 'manager', 'guard_name' => 'web']);

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $role->id,
            'subject_type' => Role::class,
            'description' => 'created',
        ]);
    }

    public function test_it_logs_login_event(): void
    {
        $user = User::factory()->create();

        event(new Login('web', $user, false));

        $this->assertDatabaseHas('activity_log', [
            'causer_id' => $user->id,
            'log_name' => 'auth',
            'event' => 'login',
        ]);
    }

    public function test_only_authorized_users_can_access_audit_logs(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user'); // Does not have audit.view permission

        $response = $this->actingAs($user)->getJson('/api/v1/audit-logs');
        $response->assertStatus(403);

        $admin = User::factory()->create();
        $admin->assignRole('admin'); // Has audit.view permission

        $response = $this->actingAs($admin)->getJson('/api/v1/audit-logs');
        $response->assertStatus(200);
    }
}
