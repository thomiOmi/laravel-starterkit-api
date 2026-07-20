<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use Modules\IAM\Payloads\V1\RegisterPayload;

describe('RegisterPayload', function () {
    it('can be constructed with all properties', function () {
        $payload = new RegisterPayload(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'secret123',
            deviceName: 'My Browser',
        );

        expect($payload->name)->toBe('John Doe');
        expect($payload->email)->toBe('john@example.com');
        expect($payload->password)->toBe('secret123');
        expect($payload->deviceName)->toBe('My Browser');
    });

    it('can be constructed without device name', function () {
        $payload = new RegisterPayload(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'secret123',
        );

        expect($payload->deviceName)->toBeNull();
    });

    it('toArray returns all required fields', function () {
        $payload = new RegisterPayload(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'secret123',
        );

        expect($payload->toArray())->toBe([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);
    });
});
