<?php

declare(strict_types=1);

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
});

describe('security:check command', function () {
    it('runs security check and shows table', function () {
        $this->artisan('security:check')
            ->expectsOutputToContain('All production security checks passed')
            ->assertSuccessful();
    });
});
