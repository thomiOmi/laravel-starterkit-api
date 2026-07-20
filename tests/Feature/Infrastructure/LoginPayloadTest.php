<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use Modules\IAM\Payloads\V1\LoginPayload;

describe('LoginPayload', function () {
    it('can be constructed with all properties', function () {
        $payload = new LoginPayload(
            email: 'test@example.com',
            password: 'secret123',
            deviceName: 'My iPhone',
        );

        expect($payload->email)->toBe('test@example.com');
        expect($payload->password)->toBe('secret123');
        expect($payload->deviceName)->toBe('My iPhone');
    });

    it('can be constructed without device name', function () {
        $payload = new LoginPayload(
            email: 'test@example.com',
            password: 'secret123',
        );

        expect($payload->deviceName)->toBeNull();
    });

    it('toArray includes device_name when set', function () {
        $payload = new LoginPayload(
            email: 'test@example.com',
            password: 'secret123',
            deviceName: 'My iPhone',
        );

        expect($payload->toArray())->toBe([
            'email' => 'test@example.com',
            'password' => 'secret123',
            'device_name' => 'My iPhone',
        ]);
    });

    it('toArray omits device_name when null', function () {
        $payload = new LoginPayload(
            email: 'test@example.com',
            password: 'secret123',
        );

        expect($payload->toArray())->toBe([
            'email' => 'test@example.com',
            'password' => 'secret123',
        ]);
    });
});
