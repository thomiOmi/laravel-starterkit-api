<?php

declare(strict_types=1);

use App\Concerns\PasswordValidationRules;

final readonly class PasswordRulesTester
{
    use PasswordValidationRules;

    public function runPasswordRules(bool $required = true, bool $confirmed = true, bool $validate = true): array
    {
        return $this->passwordRules($required, $confirmed, $validate);
    }

    public function runCurrentPasswordRules(): array
    {
        return $this->currentPasswordRules();
    }
}

test('password rules include required by default', function () {
    $rules = (new PasswordRulesTester)->runPasswordRules();

    expect($rules)->toContain('required');
    expect($rules)->toContain('string');
    expect($rules)->toContain('confirmed');
});

test('password rules exclude required when set to false', function () {
    $rules = (new PasswordRulesTester)->runPasswordRules(required: false);

    expect($rules)->toContain('nullable');
    expect($rules)->not->toContain('required');
});

test('password rules exclude confirmed when set to false', function () {
    $rules = (new PasswordRulesTester)->runPasswordRules(confirmed: false);

    expect($rules)->not->toContain('confirmed');
});

test('password rules exclude validation when set to false', function () {
    $rules = (new PasswordRulesTester)->runPasswordRules(validate: false, confirmed: false);

    expect($rules)->toHaveCount(3);
    expect($rules[0])->toBe('required');
    expect($rules[1])->toBe('string');
    expect($rules[2])->toBe('max:255');
});

test('current password rules are always the same', function () {
    $rules = (new PasswordRulesTester)->runCurrentPasswordRules();

    expect($rules)->toBe(['required', 'string', 'max:255', 'current_password']);
});
