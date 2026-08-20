<?php

declare(strict_types=1);

namespace App\Support\Production;

use InvalidArgumentException;

/**
 * Validate environment configuration for production safety.
 *
 * Each check returns a 'pass' or 'fail' verdict so the caller can
 * surface results in a CLI table, monitoring endpoint, or CI gate.
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
            $this->checkFrontendUrl(),
            $this->checkTrustedHosts(),
            $this->checkAppKey(),
            $this->checkCacheStore(),
            $this->checkSessionDriver(),
            $this->checkQueueConnection(),
            $this->checkSessionSecureCookie(),
            $this->checkSessionSameSite(),
            $this->checkMailMailer(),
            $this->checkLogChannel(),
            $this->checkMailFromAddress(),
        ];
    }

    /**
     * @return array{check: string, status: string, detail: string}
     */
    private function checkAppDebug(): array
    {
        $debug = config()->boolean('app.debug', true);

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
        $env = config()->string('app.env', 'production');

        return [
            'check' => 'APP_ENV',
            'status' => $env === 'production' ? 'pass' : 'fail',
            'detail' => $env === 'production'
                ? 'APP_ENV is set to production'
                : "APP_ENV is '$env', expected 'production'",
        ];
    }

    /**
     * @return array{check: string, status: string, detail: string}
     */
    private function checkAppUrl(): array
    {
        $url = config()->string('app.url', '');

        return [
            'check' => 'APP_URL',
            'status' => str_starts_with($url, 'https://') ? 'pass' : 'fail',
            'detail' => str_starts_with($url, 'https://')
                ? 'APP_URL uses HTTPS'
                : "APP_URL '$url' must use HTTPS in production",
        ];
    }

    /**
     * @return array{check: string, status: string, detail: string}
     *
     * @throws InvalidArgumentException When the configured value is not a string.
     */
    private function checkFrontendUrl(): array
    {
        $url = config()->string('app.frontend_url', '');

        return [
            'check' => 'FRONTEND_URL',
            'status' => str_starts_with($url, 'https://') ? 'pass' : 'fail',
            'detail' => str_starts_with($url, 'https://')
                ? 'FRONTEND_URL uses HTTPS'
                : "FRONTEND_URL '$url' must use HTTPS in production",
        ];
    }

    /**
     * @return array{check: string, status: string, detail: string}
     */
    private function checkTrustedHosts(): array
    {
        $hosts = config()->array('app.trusted_hosts', []);

        return [
            'check' => 'TRUSTED_HOSTS',
            'status' => $hosts !== [] ? 'pass' : 'fail',
            'detail' => $hosts !== []
                ? 'TRUSTED_HOSTS has '.count($hosts).' trusted host(s)'
                : 'TRUSTED_HOSTS is empty, at least one trusted host must be configured to prevent host header poisoning',
        ];
    }

    /**
     * @return array{check: string, status: string, detail: string}
     *
     * @throws InvalidArgumentException When the configured value is not a string.
     */
    private function checkAppKey(): array
    {
        $key = config()->string('app.key', '');

        $isValid = $key !== '' && str_starts_with($key, 'base64:') && strlen($key) > 7;

        return [
            'check' => 'APP_KEY',
            'status' => $isValid ? 'pass' : 'fail',
            'detail' => $isValid
                ? 'APP_KEY is set and uses base64 format'
                : 'APP_KEY is missing, is the default placeholder, or is not in base64 format',
        ];
    }

    /**
     * @return array{check: string, status: string, detail: string}
     */
    private function checkCacheStore(): array
    {
        $store = config()->string('cache.default', 'file');

        return [
            'check' => 'CACHE_STORE',
            'status' => $store !== 'array' ? 'pass' : 'fail',
            'detail' => $store !== 'array'
                ? "CACHE_STORE is '$store'"
                : "CACHE_STORE is 'array', use redis or database in production",
        ];
    }

    /**
     * @return array{check: string, status: string, detail: string}
     */
    private function checkSessionDriver(): array
    {
        $driver = config()->string('session.driver', 'file');

        return [
            'check' => 'SESSION_DRIVER',
            'status' => $driver !== 'file' ? 'pass' : 'fail',
            'detail' => $driver !== 'file'
                ? "SESSION_DRIVER is '$driver'"
                : "SESSION_DRIVER is 'file', use redis or database in production",
        ];
    }

    /**
     * @return array{check: string, status: string, detail: string}
     */
    private function checkQueueConnection(): array
    {
        $connection = config()->string('queue.default', 'sync');

        return [
            'check' => 'QUEUE_CONNECTION',
            'status' => $connection !== 'sync' ? 'pass' : 'fail',
            'detail' => $connection !== 'sync'
                ? "QUEUE_CONNECTION is '$connection'"
                : "QUEUE_CONNECTION is 'sync', use redis or database in production",
        ];
    }

    /**
     * @return array{check: string, status: string, detail: string}
     */
    private function checkSessionSecureCookie(): array
    {
        $secure = config('session.secure');

        $isSecure = $secure === true;

        return [
            'check' => 'SESSION_SECURE_COOKIE',
            'status' => $isSecure ? 'pass' : 'fail',
            'detail' => $isSecure
                ? 'SESSION_SECURE_COOKIE is enabled'
                : 'SESSION_SECURE_COOKIE is disabled, must be true when using HTTPS',
        ];
    }

    /**
     * @return array{check: string, status: string, detail: string}
     */
    private function checkSessionSameSite(): array
    {
        $sameSite = config()->string('session.same_site', 'lax');

        return [
            'check' => 'SESSION_SAME_SITE',
            'status' => in_array($sameSite, ['lax', 'strict'], true) ? 'pass' : 'fail',
            'detail' => in_array($sameSite, ['lax', 'strict'], true)
                ? "SESSION_SAME_SITE is '$sameSite'"
                : "SESSION_SAME_SITE is '$sameSite', use lax or strict in production to mitigate CSRF",
        ];
    }

    /**
     * @return array{check: string, status: string, detail: string}
     */
    private function checkMailMailer(): array
    {
        $mailer = config()->string('mail.default', 'log');

        return [
            'check' => 'MAIL_MAILER',
            'status' => ! in_array($mailer, ['log', 'array'], true) ? 'pass' : 'fail',
            'detail' => ! in_array($mailer, ['log', 'array'], true)
                ? "MAIL_MAILER is '$mailer'"
                : "MAIL_MAILER is '$mailer', use smtp, ses, mailgun, etc. in production",
        ];
    }

    /**
     * @return array{check: string, status: string, detail: string}
     */
    private function checkLogChannel(): array
    {
        $channel = config()->string('logging.default', 'stack');

        return [
            'check' => 'LOG_CHANNEL',
            'status' => $channel !== 'single' ? 'pass' : 'fail',
            'detail' => $channel !== 'single'
                ? "LOG_CHANNEL is '$channel'"
                : "LOG_CHANNEL is 'single', use 'daily' or 'stack' in production to prevent unbounded log files",
        ];
    }

    /**
     * @return array{check: string, status: string, detail: string}
     */
    private function checkMailFromAddress(): array
    {
        $address = config()->string('mail.from.address', '');

        return [
            'check' => 'MAIL_FROM_ADDRESS',
            'status' => $address !== '' ? 'pass' : 'fail',
            'detail' => $address !== ''
                ? "MAIL_FROM_ADDRESS is '$address'"
                : 'MAIL_FROM_ADDRESS is not set',
        ];
    }
}
