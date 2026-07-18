<?php

declare(strict_types=1);

namespace App\Support\Production;

/**
 * Validate environment configuration for production safety.
 *
 * Each check returns a 'pass' or 'fail' verdict so the caller can
 * surface results in a CLI table, monitoring endpoint, or CI gate.
 *
 * @see https://github.com/JustSteveKing/kit for the original reference
 */
final readonly class ProductionSecurityCheck
{
    /**
     * @return array<int, array{check: string, status: string, detail: string}>
     */
    public function __invoke(): array
    {
        return [
            $this->checkAppDebug(),
            $this->checkAppEnv(),
            $this->checkAppUrl(),
            $this->checkAppKey(),
            $this->checkCacheStore(),
            $this->checkSessionDriver(),
        ];
    }

    /**
     * @return array{check: string, status: string, detail: string}
     */
    private function checkAppDebug(): array
    {
        $debug = config()->boolean('app.debug');

        return [
            'check' => 'APP_DEBUG',
            'status' => $debug === false ? 'pass' : 'fail',
            'detail' => $debug === false
                ? 'APP_DEBUG is disabled'
                : 'APP_DEBUG must be false in production',
        ];
    }

    /**
     * @return array{check: string, status: string, detail: string}
     */
    private function checkAppEnv(): array
    {
        $env = config()->string('app.env');

        return [
            'check' => 'APP_ENV',
            'status' => $env === 'production' ? 'pass' : 'fail',
            'detail' => $env === 'production'
                ? 'APP_ENV is set to production'
                : "APP_ENV is '{$env}', expected 'production'",
        ];
    }

    /**
     * @return array{check: string, status: string, detail: string}
     */
    private function checkAppUrl(): array
    {
        $url = config()->string('app.url');

        return [
            'check' => 'APP_URL',
            'status' => str_starts_with($url, 'https://') ? 'pass' : 'fail',
            'detail' => str_starts_with($url, 'https://')
                ? 'APP_URL uses HTTPS'
                : "APP_URL '{$url}' must use HTTPS in production",
        ];
    }

    /**
     * @return array{check: string, status: string, detail: string}
     */
    private function checkAppKey(): array
    {
        $key = config()->string('app.key');

        return [
            'check' => 'APP_KEY',
            'status' => $key !== '' && $key !== 'SomeRandomKeyWith32Characters' ? 'pass' : 'fail',
            'detail' => $key !== '' && $key !== 'SomeRandomKeyWith32Characters'
                ? 'APP_KEY is set'
                : 'APP_KEY is missing or is still the default placeholder',
        ];
    }

    /**
     * @return array{check: string, status: string, detail: string}
     */
    private function checkCacheStore(): array
    {
        $store = config()->string('cache.default');

        return [
            'check' => 'CACHE_STORE',
            'status' => $store !== 'array' ? 'pass' : 'fail',
            'detail' => $store !== 'array'
                ? "CACHE_STORE is '{$store}'"
                : "CACHE_STORE is 'array', use redis or database in production",
        ];
    }

    /**
     * @return array{check: string, status: string, detail: string}
     */
    private function checkSessionDriver(): array
    {
        $driver = config()->string('session.driver');

        return [
            'check' => 'SESSION_DRIVER',
            'status' => $driver !== 'file' ? 'pass' : 'fail',
            'detail' => $driver !== 'file'
                ? "SESSION_DRIVER is '{$driver}'"
                : "SESSION_DRIVER is 'file', use redis or database in production",
        ];
    }
}
