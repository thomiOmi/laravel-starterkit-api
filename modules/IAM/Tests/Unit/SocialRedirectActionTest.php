<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use InvalidArgumentException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialUser;
use Modules\IAM\Actions\SocialRedirectAction;

describe('SocialRedirectAction', function () {
    it('returns a redirect URL for a valid provider', function () {
        Socialite::fake('google', SocialUser::fake());

        $action = app(SocialRedirectAction::class);
        $url = $action->handle('google');

        expect($url)->toBeString()->not->toBeEmpty();
    });

    it('throws InvalidArgumentException for an invalid provider', function () {
        $action = app(SocialRedirectAction::class);

        $action->handle('facebook');
    })->throws(InvalidArgumentException::class);
});
