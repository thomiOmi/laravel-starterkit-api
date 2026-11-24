<?php

declare(strict_types=1);

use App\Features\BetaFeature;
use Illuminate\Support\Facades\Event;
use Laravel\Pennant\Events\UnexpectedNullScopeEncountered;
use Laravel\Pennant\Feature;
use Modules\IAM\Database\Seeders\IAMSeeder;

covers(BetaFeature::class);

beforeEach(function (): void {
    Feature::flushCache();
});

it('resolves true for an admin and false for a non-admin user', function (): void {
    $this->seed(IAMSeeder::class);

    $admin = loginAsAdmin();
    $nonAdmin = loginAsUserRole();

    expect((new BetaFeature)->resolve($admin))->toBeTrue()
        ->and((new BetaFeature)->resolve($nonAdmin))->toBeFalse()
        ->and(Feature::for($admin)->active(BetaFeature::class))->toBeTrue()
        ->and(Feature::for($nonAdmin)->active(BetaFeature::class))->toBeFalse();
});

it('is inactive for a guest scope without invoking the resolver', function (): void {
    Event::fake([UnexpectedNullScopeEncountered::class]);

    expect(Feature::active(BetaFeature::class))->toBeFalse();

    Event::assertDispatched(UnexpectedNullScopeEncountered::class);
});
