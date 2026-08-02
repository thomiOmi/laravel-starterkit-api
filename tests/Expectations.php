<?php

declare(strict_types=1);

use Pest\Expectation;

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
| Note: custom expect()->extend() methods are not recognized by PHPStan (pest-plugin-phpstan
| only understands built-in matchers), so response-level assertions live in tests/Helpers.php
| as typed functions. Keep this file for expect()->pipe() customizations only.
|
*/

expect()->pipe('toMatchSnapshot', function (Closure $next) {
    /** @var Expectation<mixed> $this */
    // @phpstan-ignore-next-line
    if (is_string($this->value)) {
        // @phpstan-ignore-next-line
        $this->value = preg_replace(
            '/"timestamp":"[^"]+"/',
            '"timestamp":"[dynamic]"',
            $this->value, // @phpstan-ignore-line
        ) ?? $this->value; // @phpstan-ignore-line
    }

    return $next();
});
