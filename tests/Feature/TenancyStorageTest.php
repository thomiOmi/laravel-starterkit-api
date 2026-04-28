<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Tenant\Models\Tenant;
use Tests\TestCase;

class TenancyStorageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenants
        Tenant::create(['id' => 'tenant1']);
        Tenant::create(['id' => 'tenant2']);
    }

    public function test_storage_is_isolated_between_tenants(): void
    {
        // 1. Login as Tenant 1 and save a file
        tenancy()->initialize(Tenant::find('tenant1'));
        Storage::disk('local')->put('secret.txt', 'Content for Tenant 1');
        $this->assertTrue(Storage::disk('local')->exists('secret.txt'));
        $path1 = Storage::disk('local')->path('secret.txt');
        tenancy()->end();

        // 2. Login as Tenant 2 and check the file
        tenancy()->initialize(Tenant::find('tenant2'));
        // File should NOT exist here
        $this->assertFalse(Storage::disk('local')->exists('secret.txt'));

        // Save a different file for Tenant 2
        Storage::disk('local')->put('secret.txt', 'Content for Tenant 2');
        $path2 = Storage::disk('local')->path('secret.txt');
        tenancy()->end();

        // 3. Verify physical paths are different
        $this->assertNotEquals($path1, $path2);
        $this->assertStringContainsString('tenant1', $path1);
        $this->assertStringContainsString('tenant2', $path2);

        // Cleanup
        tenancy()->initialize(Tenant::find('tenant1'));
        Storage::disk('local')->delete('secret.txt');
        tenancy()->end();

        tenancy()->initialize(Tenant::find('tenant2'));
        Storage::disk('local')->delete('secret.txt');
        tenancy()->end();
    }
}
