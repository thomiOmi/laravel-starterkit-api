<?php

declare(strict_types=1);

/**
 * Load a translation file and ensure it returns an array.
 *
 * @return array<array-key, mixed>
 */
function loadTranslationFile(string $path): array
{
    $lines = require $path;

    throw_unless(is_array($lines), RuntimeException::class, "Translation file [{$path}] must return an array.");

    return $lines;
}

/**
 * Flatten a translation file into dot-notation keys.
 *
 * Only leaf string values are returned; nested arrays are expanded.
 *
 * @param  array<array-key, mixed>  $lines
 * @return list<string>
 */
function flattenTranslationKeys(array $lines, string $prefix = ''): array
{
    $keys = [];

    foreach ($lines as $key => $value) {
        $full = $prefix === '' ? (string) $key : $prefix.'.'.$key;

        if (is_array($value)) {
            $keys = [...$keys, ...flattenTranslationKeys($value, $full)];

            continue;
        }

        $keys[] = $full;
    }

    return $keys;
}

describe('translation resolution', function (): void {

    it('resolves every root and IAM translation key', function (string $locale): void {
        app()->setLocale($locale);

        $keys = [];

        foreach (glob(lang_path($locale).'/*.php') ?: [] as $file) {
            $group = basename($file, '.php');

            $keys = [...$keys, ...array_map(
                static fn (string $key): string => $group.'.'.$key,
                flattenTranslationKeys(loadTranslationFile($file)),
            )];
        }

        foreach (glob(module_path('IAM', 'lang/'.$locale).'/*.php') ?: [] as $file) {
            $group = basename($file, '.php');

            $keys = [...$keys, ...array_map(
                static fn (string $key): string => 'iam::'.$group.'.'.$key,
                flattenTranslationKeys(loadTranslationFile($file)),
            )];
        }

        expect($keys)->not->toBeEmpty();

        foreach ($keys as $key) {
            expect(trans()->hasForLocale($key, $locale))->toBeTrue("Translation key [{$key}] is missing for locale [{$locale}].");
        }
    })->with(['en', 'id']);

    it('resolves the keys code calls without a namespace', function (string $locale): void {
        app()->setLocale($locale);

        $keys = [
            'auth.failed',
            'auth.password',
            'auth.throttle',
            'auth.http_gone',
            'auth.unauthenticated',
            'auth.http_forbidden',
            'auth.http_validation_failed',
            'auth.account_banned',
            'auth.email_verify_subject',
            'auth.password_reset_expire',
            'auth.internal_error_detail',
            'passwords.reset',
            'passwords.sent',
            'passwords.throttled',
            'passwords.token',
            'passwords.user',
            'iam::auth.register_success',
            'iam::auth.social_unlink_blocked',
        ];

        foreach ($keys as $key) {
            expect(trans()->hasForLocale($key, $locale))->toBeTrue("Translation key [{$key}] is missing for locale [{$locale}].");
        }
    })->with(['en', 'id']);

});
