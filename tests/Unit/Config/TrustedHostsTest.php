<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpFoundation\Request;

describe('trusted host patterns', function (): void {
    afterEach(function (): void {
        // Symfony keeps trusted host patterns in static state; clear it so
        // this file does not leak host restrictions into other tests.
        Request::setTrustedHosts([]);
    });

    it('anchors every APP_TRUSTED_HOSTS entry', function (): void {
        expect(evaluateTrustedHostPatterns('example.com'))
            ->toBe(['^example\.com$']);
    });

    it('discards blank entries before anchoring', function (): void {
        expect(evaluateTrustedHostPatterns('example.com, ,attacker.com,'))
            ->toBe(['^example\.com$', '^attacker\.com$']);
    });

    it('writes each listed host only once', function (): void {
        expect(evaluateTrustedHostPatterns('example.com,example.com'))
            ->toBe(['^example\.com$']);
    });

    it('anchors the default localhost and application URL hosts', function (): void {
        expect(evaluateTrustedHostPatterns(null, 'https://api.example.com'))
            ->toBe(['^localhost$', '^127\.0\.0\.1$', '^api\.example\.com$']);
    });
});

describe('host header validation with configured patterns', function (): void {
    afterEach(function (): void {
        Request::setTrustedHosts([]);
    });

    it('accepts the host that is listed', function (): void {
        Request::setTrustedHosts(evaluateTrustedHostPatterns('example.com'));

        $request = Request::create('/', server: ['HTTP_HOST' => 'example.com']);

        expect($request->getHost())->toBe('example.com');
    });

    it('accepts a host header written in uppercase', function (): void {
        Request::setTrustedHosts(evaluateTrustedHostPatterns('example.com'));

        $request = Request::create('/', server: ['HTTP_HOST' => 'EXAMPLE.com']);

        expect($request->getHost())->toBe('example.com');
    });

    it('rejects the listed host with an attacker domain appended', function (): void {
        Request::setTrustedHosts(evaluateTrustedHostPatterns('example.com'));

        $request = Request::create('/', server: ['HTTP_HOST' => 'example.com.attacker.org']);

        expect(fn () => $request->getHost())->toThrow(SuspiciousOperationException::class);
    });

    it('rejects a prefixed lookalike of the listed host', function (): void {
        Request::setTrustedHosts(evaluateTrustedHostPatterns('example.com'));

        $request = Request::create('/', server: ['HTTP_HOST' => 'evil-example.com']);

        expect(fn () => $request->getHost())->toThrow(SuspiciousOperationException::class);
    });

    it('rejects a subdomain when only the apex is listed', function (): void {
        Request::setTrustedHosts(evaluateTrustedHostPatterns('example.com'));

        $request = Request::create('/', server: ['HTTP_HOST' => 'sub.example.com']);

        expect(fn () => $request->getHost())->toThrow(SuspiciousOperationException::class);
    });
});

/**
 * Evaluate config/app.php and return the trusted_hosts patterns it builds.
 *
 * The patterns are derived from the environment, so the config file is
 * re-required with temporary APP_TRUSTED_HOSTS and APP_URL values. Calling
 * Env::getRepository()->set() cannot overwrite variables dotenv already
 * defined (the immutable writer rejects them), so the overrides are written
 * to the underlying stores ($_ENV, $_SERVER, and the process environment)
 * directly and every key is restored in a finally block.
 *
 * @param  string|null  $trustedHosts  temporary APP_TRUSTED_HOSTS value, null to test the defaults
 * @param  string|null  $appUrl  temporary APP_URL value, null to keep the environment value
 * @return array<int, string>
 */
function evaluateTrustedHostPatterns(?string $trustedHosts, ?string $appUrl = null): array
{
    $originalTrustedHosts = swapEnv('APP_TRUSTED_HOSTS', $trustedHosts);
    $originalAppUrl = $appUrl === null ? null : swapEnv('APP_URL', $appUrl);

    try {
        /** @var array<string, mixed> $evaluated */
        $evaluated = require config_path('app.php');

        /** @var array<int, string> $patterns */
        $patterns = $evaluated['trusted_hosts'];

        return $patterns;
    } finally {
        if ($appUrl !== null) {
            swapEnv('APP_URL', $originalAppUrl);
        }

        swapEnv('APP_TRUSTED_HOSTS', $originalTrustedHosts);
    }
}

/**
 * Overwrite the value of an environment variable across the dotenv
 * underlying stores and return the previous value, or null when the
 * variable was not set before. Passing null as $value removes the variable.
 */
function swapEnv(string $key, ?string $value): ?string
{
    $original = getenv($key);

    $original = $original === false ? null : $original;

    if ($value === null) {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);

        return $original;
    }

    putenv($key.'='.$value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;

    return $original;
}
