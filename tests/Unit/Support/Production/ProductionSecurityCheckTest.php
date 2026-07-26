<?php

declare(strict_types=1);

use App\Support\Production\ProductionSecurityCheck;

covers(ProductionSecurityCheck::class);

beforeEach(function () {
    config()->set('app.debug', false);
    config()->set('app.env', 'production');
    config()->set('app.url', 'https://example.com');
    config()->set('cache.default', 'redis');
    config()->set('session.driver', 'redis');
    config()->set('session.secure', true);
    config()->set('queue.default', 'redis');
    config()->set('mail.default', 'smtp');
    config()->set('mail.from.address', 'noreply@example.com');
    config()->set('logging.default', 'daily');
    config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
});

describe('production config', function () {
    it('passes all checks with production config', function () {
        $result = (new ProductionSecurityCheck)();

        $failed = array_filter($result, fn (array $r): bool => $r['status'] === 'fail');

        expect($failed)->toBeEmpty();
        expect($result)->toHaveCount(11);
    });
});

describe('failure cases', function () {
    it('fails when misconfigured', function (string $configKey, string|bool $configValue, string $checkName) {
        config()->set($configKey, $configValue);

        $result = (new ProductionSecurityCheck)();
        $check = findCheck($result, $checkName);

        expect($check['status'])->toBe('fail');
    })->with('securityFailCases');
});

describe('multiple failures', function () {
    it('returns multiple failures at once', function () {
        config()->set('app.debug', true);
        config()->set('app.env', 'local');
        config()->set('app.url', 'http://insecure.com');

        $result = (new ProductionSecurityCheck)();
        $failed = array_filter($result, fn (array $r): bool => $r['status'] === 'fail');

        expect($failed)->toHaveCount(3);
    });
});

function findCheck(array $results, string $name): array
{
    foreach ($results as $result) {
        if ($result['check'] === $name) {
            return $result;
        }
    }

    return ['check' => $name, 'status' => 'fail', 'detail' => 'not found'];
}
