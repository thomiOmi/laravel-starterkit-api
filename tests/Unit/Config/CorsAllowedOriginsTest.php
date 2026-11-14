<?php

declare(strict_types=1);

describe('cors allowed origins', function (): void {
    it('trims surrounding spaces from every listed origin', function (): void {
        expect(evaluateCorsAllowedOrigins('https://a.test, https://b.test'))
            ->toBe(['https://a.test', 'https://b.test']);
    });

    it('allows no origin when the variable is set to an empty string', function (): void {
        expect(evaluateCorsAllowedOrigins(''))->toBeEmpty();
    });

    it('falls back to the default local origins when the variable is not set', function (): void {
        expect(evaluateCorsAllowedOrigins(null))
            ->toBe(['http://localhost:3000', 'http://localhost:5173', 'http://localhost:8080']);
    });

    it('discards blank entries produced by repeated commas and extra spaces', function (): void {
        expect(evaluateCorsAllowedOrigins('https://a.test,  ,https://b.test,,'))
            ->toBe(['https://a.test', 'https://b.test']);
    });
});

/**
 * Evaluate config/cors.php and return the allowed_origins list it builds.
 *
 * env() reads the dotenv repository, whose immutable writer rejects writes
 * for variables dotenv already defined, so the override is written straight
 * to the three stores the repository reads ($_SERVER, $_ENV, and the process
 * environment) and every key is restored in a finally block.
 *
 * @param  string|null  $origins  temporary CORS_ALLOWED_ORIGINS value, null to unset it
 * @return array<int, string>
 */
function evaluateCorsAllowedOrigins(?string $origins): array
{
    $original = getenv('CORS_ALLOWED_ORIGINS');

    $original = $original === false ? null : $original;

    if ($origins === null) {
        putenv('CORS_ALLOWED_ORIGINS');
        unset($_ENV['CORS_ALLOWED_ORIGINS'], $_SERVER['CORS_ALLOWED_ORIGINS']);
    } else {
        putenv('CORS_ALLOWED_ORIGINS='.$origins);
        $_ENV['CORS_ALLOWED_ORIGINS'] = $origins;
        $_SERVER['CORS_ALLOWED_ORIGINS'] = $origins;
    }

    try {
        /** @var array<string, mixed> $evaluated */
        $evaluated = require config_path('cors.php');

        /** @var array<int, string> $allowedOrigins */
        $allowedOrigins = $evaluated['allowed_origins'];

        return $allowedOrigins;
    } finally {
        if ($original === null) {
            putenv('CORS_ALLOWED_ORIGINS');
            unset($_ENV['CORS_ALLOWED_ORIGINS'], $_SERVER['CORS_ALLOWED_ORIGINS']);
        } else {
            putenv('CORS_ALLOWED_ORIGINS='.$original);
            $_ENV['CORS_ALLOWED_ORIGINS'] = $original;
            $_SERVER['CORS_ALLOWED_ORIGINS'] = $original;
        }
    }
}
