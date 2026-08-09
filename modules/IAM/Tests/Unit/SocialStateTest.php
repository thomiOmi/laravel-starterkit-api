<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Modules\IAM\Support\SocialState;

describe('SocialState', function (): void {
    it('creates a valid state token with the given action', function (): void {
        $state = SocialState::create('login');

        expect($state)->not->toBeEmpty();
        expect(SocialState::verify($state)['action'])->toBe('login');
    })->group('module:iam');

    it('embeds the payload for link actions', function (): void {
        $state = SocialState::create('link', ['user_id' => '01JX0ABCDEFGHIJKLMNOPQRST']);

        $payload = SocialState::verify($state);

        expect($payload['action'])->toBe('link');
        expect($payload['user_id'] ?? null)->toBe('01JX0ABCDEFGHIJKLMNOPQRST');
    })->group('module:iam');

    it('rejects a tampered state token', function (): void {
        $state = SocialState::create('login').'tampered';

        expect(fn () => SocialState::verify($state))
            ->toThrow(InvalidArgumentException::class, __('validation.social_state_invalid'));
    })->group('module:iam');

    it('rejects an expired state token', function (): void {
        $state = SocialState::create('login');

        Carbon::setTestNow(now()->addMinutes(11));

        expect(fn () => SocialState::verify($state))
            ->toThrow(InvalidArgumentException::class, __('validation.social_state_expired'));
    })->group('module:iam');

    afterEach(function (): void {
        Carbon::setTestNow();
    });
});
