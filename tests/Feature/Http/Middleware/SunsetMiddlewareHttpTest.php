<?php

declare(strict_types=1);

use App\Http\Middleware\AddDeprecationHeaders;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

covers(AddDeprecationHeaders::class);

function sunsetTestTimezone(?string $timezone = null): string
{
    static $stored = 'UTC';

    if ($timezone !== null) {
        $stored = $timezone;
    }

    return $stored;
}

describe('sunset middleware over HTTP', function (): void {
    beforeEach(function (): void {
        sunsetTestTimezone(date_default_timezone_get());
        date_default_timezone_set('Asia/Jakarta');
    });

    afterEach(function (): void {
        date_default_timezone_set(sunsetTestTimezone());
    });

    it('resolves the sunset alias from a test route', function (): void {
        Route::middleware('sunset:2099-01-01')->get('/_test/sunset-alias', fn (): JsonResponse => response()->json(['ok' => true]));

        $response = $this->getJson('/_test/sunset-alias');

        $response->assertOk()
            ->assertHeader('Deprecation')
            ->assertHeader('Sunset');
    });

    it('sends the exact header values computed independently', function (): void {
        Route::middleware('sunset:2099-01-01')->get('/_test/sunset-values', fn (): JsonResponse => response()->json(['ok' => true]));

        // date_default_timezone_set('Asia/Jakarta'); DateTimeImmutable::createFromFormat('!Y-m-d', '2099-01-01'), then ->setTimezone(new DateTimeZone('UTC'))
        $this->getJson('/_test/sunset-values')
            ->assertOk()
            ->assertHeader('Deprecation', '@4070883600')
            ->assertHeader('Sunset', 'Wed, 31 Dec 2098 17:00:00 GMT');
    });

    it('appends a successor Link without overwriting an existing one', function (): void {
        Route::middleware('sunset:2099-01-01,https://v2.example.com/resource')
            ->get('/_test/sunset-link', fn (): JsonResponse => response()->json(['ok' => true])
                ->header('Link', '<https://docs.example.com/old>; rel="alternate"'));

        $this->getJson('/_test/sunset-link')
            ->assertOk()
            ->assertHeader('Link', '<https://docs.example.com/old>; rel="alternate", <https://v2.example.com/resource>; rel="successor-version"');
    });

    it('returns a problem response with headers when enforce is past the sunset date', function (): void {
        Route::middleware('sunset:2020-01-01,enforce')->get('/_test/sunset-enforce-past', fn (): JsonResponse => response()->json(['ok' => true]));

        $response = $this->getJson('/_test/sunset-enforce-past');

        assertProblemResponse($response, 410, 'resource-gone');
        $response->assertHeader('Deprecation', '@1577811600')
            ->assertHeader('Sunset', 'Tue, 31 Dec 2019 17:00:00 GMT');
    });

    it('passes through with headers when enforce is before the sunset date', function (): void {
        Route::middleware('sunset:2099-01-01,enforce')->get('/_test/sunset-enforce-future', fn (): JsonResponse => response()->json(['ok' => true]));

        $this->getJson('/_test/sunset-enforce-future')
            ->assertOk()
            ->assertHeader('Deprecation', '@4070883600')
            ->assertHeader('Sunset', 'Wed, 31 Dec 2098 17:00:00 GMT');
    });

    it('does not block a past date without enforce', function (): void {
        Route::middleware('sunset:2020-01-01')->get('/_test/sunset-past', fn (): JsonResponse => response()->json(['ok' => true]));

        $this->getJson('/_test/sunset-past')
            ->assertOk()
            ->assertHeader('Deprecation', '@1577811600')
            ->assertHeader('Sunset', 'Tue, 31 Dec 2019 17:00:00 GMT');
    });
});
