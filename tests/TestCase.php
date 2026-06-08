<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Modules\User\Models\User;

/**
 * @method $this actingAs(User $user, ?string $guard = null)
 * @method \Illuminate\Testing\TestResponse getJson(string $uri, array $headers = [])
 * @method \Illuminate\Testing\TestResponse postJson(string $uri, array $data = [], array $headers = [])
 * @method \Illuminate\Testing\TestResponse putJson(string $uri, array $data = [], array $headers = [])
 * @method \Illuminate\Testing\TestResponse patchJson(string $uri, array $data = [], array $headers = [])
 * @method \Illuminate\Testing\TestResponse deleteJson(string $uri, array $data = [], array $headers = [])
 * @method $this withHeader(string $name, string $value)
 * @method $this seed(array|string $class = [])
 * @method $this assertDatabaseHas(string $table, array $data)
 * @method $this assertDatabaseMissing(string $table, array $data)
 * @method $this assertSoftDeleted(string $table, array $data)
 * @method $this assertTrue(bool $condition, string $message = '')
 * @method $this assertFalse(bool $condition, string $message = '')
 * @method int artisan(string $command, array $parameters = [])
 */
abstract class TestCase extends BaseTestCase
{
    //
}
