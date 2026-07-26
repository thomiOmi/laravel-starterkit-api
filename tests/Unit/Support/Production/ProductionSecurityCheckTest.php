<?php

declare(strict_types=1);

use App\Support\Production\ProductionSecurityCheck;

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

test('passes all checks with production config', function () {
    $result = (new ProductionSecurityCheck)();

    $failed = array_filter($result, fn (array $r): bool => $r['status'] === 'fail');

    expect($failed)->toBeEmpty();
    expect($result)->toHaveCount(11);
});

test('fails when app debug is on', function () {
    config()->set('app.debug', true);

    $result = (new ProductionSecurityCheck)();
    $check = findCheck($result, 'APP_DEBUG');

    expect($check['status'])->toBe('fail');
});

test('fails when app env is not production', function () {
    config()->set('app.env', 'local');

    $result = (new ProductionSecurityCheck)();
    $check = findCheck($result, 'APP_ENV');

    expect($check['status'])->toBe('fail');
});

test('fails when app url is not https', function () {
    config()->set('app.url', 'http://example.com');

    $result = (new ProductionSecurityCheck)();
    $check = findCheck($result, 'APP_URL');

    expect($check['status'])->toBe('fail');
});

test('fails when app url is empty', function () {
    config()->set('app.url', '');

    $result = (new ProductionSecurityCheck)();
    $check = findCheck($result, 'APP_URL');

    expect($check['status'])->toBe('fail');
});

test('fails when app key is missing', function () {
    config()->set('app.key', '');

    $result = (new ProductionSecurityCheck)();
    $check = findCheck($result, 'APP_KEY');

    expect($check['status'])->toBe('fail');
});

test('fails when app key is not base64', function () {
    config()->set('app.key', 'some-invalid-key');

    $result = (new ProductionSecurityCheck)();
    $check = findCheck($result, 'APP_KEY');

    expect($check['status'])->toBe('fail');
});

test('fails when cache store is array', function () {
    config()->set('cache.default', 'array');

    $result = (new ProductionSecurityCheck)();
    $check = findCheck($result, 'CACHE_STORE');

    expect($check['status'])->toBe('fail');
});

test('fails when session driver is file', function () {
    config()->set('session.driver', 'file');

    $result = (new ProductionSecurityCheck)();
    $check = findCheck($result, 'SESSION_DRIVER');

    expect($check['status'])->toBe('fail');
});

test('fails when queue connection is sync', function () {
    config()->set('queue.default', 'sync');

    $result = (new ProductionSecurityCheck)();
    $check = findCheck($result, 'QUEUE_CONNECTION');

    expect($check['status'])->toBe('fail');
});

test('fails when session secure cookie is disabled', function () {
    config()->set('session.secure', false);

    $result = (new ProductionSecurityCheck)();
    $check = findCheck($result, 'SESSION_SECURE_COOKIE');

    expect($check['status'])->toBe('fail');
});

test('fails when mail mailer is log', function () {
    config()->set('mail.default', 'log');

    $result = (new ProductionSecurityCheck)();
    $check = findCheck($result, 'MAIL_MAILER');

    expect($check['status'])->toBe('fail');
});

test('fails when mail mailer is array', function () {
    config()->set('mail.default', 'array');

    $result = (new ProductionSecurityCheck)();
    $check = findCheck($result, 'MAIL_MAILER');

    expect($check['status'])->toBe('fail');
});

test('fails when log channel is single', function () {
    config()->set('logging.default', 'single');

    $result = (new ProductionSecurityCheck)();
    $check = findCheck($result, 'LOG_CHANNEL');

    expect($check['status'])->toBe('fail');
});

test('fails when mail from address is empty', function () {
    config()->set('mail.from.address', '');

    $result = (new ProductionSecurityCheck)();
    $check = findCheck($result, 'MAIL_FROM_ADDRESS');

    expect($check['status'])->toBe('fail');
});

test('returns multiple failures at once', function () {
    config()->set('app.debug', true);
    config()->set('app.env', 'local');
    config()->set('app.url', 'http://insecure.com');

    $result = (new ProductionSecurityCheck)();
    $failed = array_filter($result, fn (array $r): bool => $r['status'] === 'fail');

    expect($failed)->toHaveCount(3);
});

// Helper to find a check result by name
function findCheck(array $results, string $name): array
{
    foreach ($results as $result) {
        if ($result['check'] === $name) {
            return $result;
        }
    }

    return ['check' => $name, 'status' => 'fail', 'detail' => 'not found'];
}
