<?php

declare(strict_types=1);

dataset('securityFailCases', [
    'APP_DEBUG' => ['app.debug', true, 'APP_DEBUG'],
    'APP_ENV' => ['app.env', 'local', 'APP_ENV'],
    'APP_URL (no https)' => ['app.url', 'http://example.com', 'APP_URL'],
    'APP_URL (empty)' => ['app.url', '', 'APP_URL'],
    'APP_KEY (empty)' => ['app.key', '', 'APP_KEY'],
    'APP_KEY (invalid)' => ['app.key', 'some-invalid-key', 'APP_KEY'],
    'CACHE_STORE' => ['cache.default', 'array', 'CACHE_STORE'],
    'SESSION_DRIVER' => ['session.driver', 'file', 'SESSION_DRIVER'],
    'QUEUE_CONNECTION' => ['queue.default', 'sync', 'QUEUE_CONNECTION'],
    'SESSION_SECURE_COOKIE' => ['session.secure', false, 'SESSION_SECURE_COOKIE'],
    'SESSION_SECURE_COOKIE (unset)' => ['session.secure', null, 'SESSION_SECURE_COOKIE'],
    'SESSION_SAME_SITE (none)' => ['session.same_site', 'none', 'SESSION_SAME_SITE'],
    'TRUSTED_HOSTS (empty)' => ['app.trusted_hosts', [], 'TRUSTED_HOSTS'],
    'MAIL_MAILER (log)' => ['mail.default', 'log', 'MAIL_MAILER'],
    'MAIL_MAILER (array)' => ['mail.default', 'array', 'MAIL_MAILER'],
    'LOG_CHANNEL' => ['logging.default', 'single', 'LOG_CHANNEL'],
    'MAIL_FROM_ADDRESS' => ['mail.from.address', '', 'MAIL_FROM_ADDRESS'],
]);
