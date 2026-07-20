<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use App\Support\Production\ProductionSecurityCheck;

describe('ProductionSecurityCheck', function () {
    beforeEach(function () {
        config()->set('app.debug', false);
        config()->set('app.env', 'production');
        config()->set('app.url', 'https://example.com');
        config()->set('app.key', 'base64:'.str_repeat('a', 44));
        config()->set('cache.default', 'redis');
        config()->set('session.driver', 'redis');
        config()->set('queue.default', 'redis');
        config()->set('session.secure', true);
        config()->set('mail.default', 'smtp');
        config()->set('logging.default', 'daily');
        config()->set('mail.from.address', 'noreply@example.com');
    });

    it('passes all checks when production config is correct', function () {
        $check = app(ProductionSecurityCheck::class);
        $results = $check();

        expect($results)->toHaveCount(11);

        foreach ($results as $result) {
            expect($result['status'])->toBe('pass');
        }
    });

    describe('checkAppDebug', function () {
        it('passes when app.debug is false', function () {
            config()->set('app.debug', false);
            $results = app(ProductionSecurityCheck::class)();
            $entry = collect($results)->firstWhere('check', 'APP_DEBUG');
            expect($entry['status'])->toBe('pass');
        });

        it('fails when app.debug is true', function () {
            config()->set('app.debug', true);
            $results = app(ProductionSecurityCheck::class)();
            $entry = collect($results)->firstWhere('check', 'APP_DEBUG');
            expect($entry['status'])->toBe('fail');
        });
    });

    describe('checkAppEnv', function () {
        it('passes when app.env is production', function () {
            config()->set('app.env', 'production');
            $results = app(ProductionSecurityCheck::class)();
            $entry = collect($results)->firstWhere('check', 'APP_ENV');
            expect($entry['status'])->toBe('pass');
        });

        it('fails when app.env is not production', function () {
            config()->set('app.env', 'local');
            $results = app(ProductionSecurityCheck::class)();
            $entry = collect($results)->firstWhere('check', 'APP_ENV');
            expect($entry['status'])->toBe('fail');
        });
    });

    describe('checkAppUrl', function () {
        it('passes when app.url uses HTTPS', function () {
            config()->set('app.url', 'https://example.com');
            $results = app(ProductionSecurityCheck::class)();
            $entry = collect($results)->firstWhere('check', 'APP_URL');
            expect($entry['status'])->toBe('pass');
        });

        it('fails when app.url uses HTTP', function () {
            config()->set('app.url', 'http://example.com');
            $results = app(ProductionSecurityCheck::class)();
            $entry = collect($results)->firstWhere('check', 'APP_URL');
            expect($entry['status'])->toBe('fail');
        });
    });

    describe('checkAppKey', function () {
        it('passes when app.key is valid base64', function () {
            config()->set('app.key', 'base64:'.str_repeat('a', 44));
            $results = app(ProductionSecurityCheck::class)();
            $entry = collect($results)->firstWhere('check', 'APP_KEY');
            expect($entry['status'])->toBe('pass');
        });

        it('fails when app.key is empty', function () {
            config()->set('app.key', '');
            $results = app(ProductionSecurityCheck::class)();
            $entry = collect($results)->firstWhere('check', 'APP_KEY');
            expect($entry['status'])->toBe('fail');
        });

        it('fails when app.key is not base64 format', function () {
            config()->set('app.key', 'some-random-key');
            $results = app(ProductionSecurityCheck::class)();
            $entry = collect($results)->firstWhere('check', 'APP_KEY');
            expect($entry['status'])->toBe('fail');
        });
    });

    describe('checkCacheStore', function () {
        it('passes when cache store is not array', function () {
            config()->set('cache.default', 'redis');
            $results = app(ProductionSecurityCheck::class)();
            $entry = collect($results)->firstWhere('check', 'CACHE_STORE');
            expect($entry['status'])->toBe('pass');
        });

        it('fails when cache store is array', function () {
            config()->set('cache.default', 'array');
            $results = app(ProductionSecurityCheck::class)();
            $entry = collect($results)->firstWhere('check', 'CACHE_STORE');
            expect($entry['status'])->toBe('fail');
        });
    });

    describe('checkSessionDriver', function () {
        it('passes when session driver is not file', function () {
            config()->set('session.driver', 'redis');
            $results = app(ProductionSecurityCheck::class)();
            $entry = collect($results)->firstWhere('check', 'SESSION_DRIVER');
            expect($entry['status'])->toBe('pass');
        });

        it('fails when session driver is file', function () {
            config()->set('session.driver', 'file');
            $results = app(ProductionSecurityCheck::class)();
            $entry = collect($results)->firstWhere('check', 'SESSION_DRIVER');
            expect($entry['status'])->toBe('fail');
        });
    });

    describe('checkQueueConnection', function () {
        it('passes when queue connection is not sync', function () {
            config()->set('queue.default', 'redis');
            $results = app(ProductionSecurityCheck::class)();
            $entry = collect($results)->firstWhere('check', 'QUEUE_CONNECTION');
            expect($entry['status'])->toBe('pass');
        });

        it('fails when queue connection is sync', function () {
            config()->set('queue.default', 'sync');
            $results = app(ProductionSecurityCheck::class)();
            $entry = collect($results)->firstWhere('check', 'QUEUE_CONNECTION');
            expect($entry['status'])->toBe('fail');
        });
    });

    describe('checkSessionSecureCookie', function () {
        it('passes when session secure cookie is enabled', function () {
            config()->set('session.secure', true);
            $results = app(ProductionSecurityCheck::class)();
            $entry = collect($results)->firstWhere('check', 'SESSION_SECURE_COOKIE');
            expect($entry['status'])->toBe('pass');
        });

        it('fails when session secure cookie is disabled', function () {
            config()->set('session.secure', false);
            $results = app(ProductionSecurityCheck::class)();
            $entry = collect($results)->firstWhere('check', 'SESSION_SECURE_COOKIE');
            expect($entry['status'])->toBe('fail');
        });
    });

    describe('checkMailMailer', function () {
        it('passes when mail mailer is not log or array', function () {
            config()->set('mail.default', 'smtp');
            $results = app(ProductionSecurityCheck::class)();
            $entry = collect($results)->firstWhere('check', 'MAIL_MAILER');
            expect($entry['status'])->toBe('pass');
        });

        it('fails when mail mailer is log', function () {
            config()->set('mail.default', 'log');
            $results = app(ProductionSecurityCheck::class)();
            $entry = collect($results)->firstWhere('check', 'MAIL_MAILER');
            expect($entry['status'])->toBe('fail');
        });

        it('fails when mail mailer is array', function () {
            config()->set('mail.default', 'array');
            $results = app(ProductionSecurityCheck::class)();
            $entry = collect($results)->firstWhere('check', 'MAIL_MAILER');
            expect($entry['status'])->toBe('fail');
        });
    });

    describe('checkLogChannel', function () {
        it('passes when log channel is not single', function () {
            config()->set('logging.default', 'daily');
            $results = app(ProductionSecurityCheck::class)();
            $entry = collect($results)->firstWhere('check', 'LOG_CHANNEL');
            expect($entry['status'])->toBe('pass');
        });

        it('fails when log channel is single', function () {
            config()->set('logging.default', 'single');
            $results = app(ProductionSecurityCheck::class)();
            $entry = collect($results)->firstWhere('check', 'LOG_CHANNEL');
            expect($entry['status'])->toBe('fail');
        });
    });

    describe('checkMailFromAddress', function () {
        it('passes when mail from address is set', function () {
            config()->set('mail.from.address', 'noreply@example.com');
            $results = app(ProductionSecurityCheck::class)();
            $entry = collect($results)->firstWhere('check', 'MAIL_FROM_ADDRESS');
            expect($entry['status'])->toBe('pass');
        });

        it('fails when mail from address is empty', function () {
            config()->set('mail.from.address', '');
            $results = app(ProductionSecurityCheck::class)();
            $entry = collect($results)->firstWhere('check', 'MAIL_FROM_ADDRESS');
            expect($entry['status'])->toBe('fail');
        });
    });
});
