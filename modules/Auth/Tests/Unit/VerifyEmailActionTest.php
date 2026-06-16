<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Actions\VerifyEmailAction;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

it('verifies email with valid id and hash', function () {
    $user = User::factory()->unverified()->create();
    $action = app(VerifyEmailAction::class);

    $result = $action->handle($user->getKey(), sha1($user->getEmailForVerification()));

    expect($result)->not->toBeNull();
    expect($result->fresh()->email_verified_at)->not->toBeNull();
});

it('returns null for invalid hash', function () {
    $user = User::factory()->unverified()->create();
    $action = app(VerifyEmailAction::class);

    $result = $action->handle($user->getKey(), sha1('wrong-email@example.com'));

    expect($result)->toBeNull();
    expect($user->fresh()->email_verified_at)->toBeNull();
});

it('returns null for non-existent user', function () {
    $action = app(VerifyEmailAction::class);

    $result = $action->handle('non-existent-id', sha1('test@example.com'));

    expect($result)->toBeNull();
});

it('returns already verified user without re-marking', function () {
    $user = User::factory()->create();
    $action = app(VerifyEmailAction::class);

    $result = $action->handle($user->getKey(), sha1($user->getEmailForVerification()));

    expect($result)->not->toBeNull();
    expect($result->fresh()->email_verified_at)->not->toBeNull();
});

it('does not modify already verified timestamp', function () {
    $user = User::factory()->create();
    $originalVerifiedAt = $user->email_verified_at->format('Y-m-d H:i:s');
    $action = app(VerifyEmailAction::class);

    $action->handle($user->getKey(), sha1($user->getEmailForVerification()));

    expect($user->fresh()->email_verified_at->format('Y-m-d H:i:s'))->toBe($originalVerifiedAt);
});
