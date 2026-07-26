<?php

declare(strict_types=1);

use App\Concerns\ProfileValidationRules;
use Illuminate\Validation\Rules\Unique;

final readonly class ProfileRulesTester
{
    use ProfileValidationRules;

    public function runProfileRules(?string $userId = null, bool $unique = true): array
    {
        return $this->profileRules($userId, $unique);
    }

    public function runNameRules(): array
    {
        return $this->nameRules();
    }

    public function runEmailRules(?string $userId = null, bool $unique = true): array
    {
        return $this->emailRules($userId, $unique);
    }
}

test('profile rules include name and email', function () {
    $rules = (new ProfileRulesTester)->runProfileRules();

    expect($rules)->toHaveKeys(['name', 'email']);
});

test('name rules are required string max 255', function () {
    expect((new ProfileRulesTester)->runNameRules())->toBe(['required', 'string', 'max:255']);
});

test('email rules include required string email max 255', function () {
    $rules = (new ProfileRulesTester)->runEmailRules();

    expect($rules)->toContain('required', 'string', 'email', 'max:255');
});

test('email rules include unique rule when unique is true', function () {
    $rules = (new ProfileRulesTester)->runEmailRules();

    $hasUnique = collect($rules)->contains(fn (mixed $rule): bool => $rule instanceof Unique);

    expect($hasUnique)->toBeTrue();
});

test('email rules exclude unique rule when unique is false', function () {
    $rules = (new ProfileRulesTester)->runEmailRules(unique: false);

    $hasUnique = collect($rules)->contains(fn (mixed $rule): bool => $rule instanceof Unique);

    expect($hasUnique)->toBeFalse();
});

test('email rules ignores userId without unique', function () {
    $rules = (new ProfileRulesTester)->runEmailRules(userId: '01ARZ3NDEKTSV4RRFFQ69G5FAV', unique: false);

    expect($rules)->toBe(['required', 'string', 'email', 'max:255']);
});
