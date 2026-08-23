<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Modules\IAM\Support\SocialState;

covers(SocialState::class);

describe('SocialState', function () {
    it('round-trips a login state', function () {
        $state = SocialState::create('login');

        $payload = SocialState::verify($state);

        expect($payload['action'])->toBe('login');
    });

    it('round-trips a link state with the target user id', function () {
        $state = SocialState::create('link', ['user_id' => '01USER']);

        $payload = SocialState::verify($state);

        expect($payload)->toHaveKey('user_id', '01USER')
            ->and($payload['action'])->toBe('link');
    });

    it('rejects an expired state', function () {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));
        $state = SocialState::create('login');
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:10:01'));

        expect(fn (): array => SocialState::verify($state))
            ->toThrow(InvalidArgumentException::class, __('validation.social_state_expired'));

        Carbon::setTestNow();
    });

    it('rejects tampered or garbage tokens', function (string $bad) {
        expect(fn (): array => SocialState::verify($bad))
            ->toThrow(InvalidArgumentException::class, __('validation.social_state_invalid'));
    })->with([
        'random text' => 'not-a-token',
        'empty' => '',
    ]);
});
