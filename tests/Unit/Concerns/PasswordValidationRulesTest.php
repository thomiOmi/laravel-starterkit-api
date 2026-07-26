<?php

declare(strict_types=1);

use App\Concerns\PasswordValidationRules;

covers(PasswordValidationRules::class);

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

describe('PasswordValidationRules', function () {

    describe('password rules', function () {
        it('include required by default', function () {
            $rules = (new PasswordRulesTester)->runPasswordRules();

            expect($rules)->toContain('required', 'string', 'confirmed');
        });

        it('exclude required when set to false', function () {
            $rules = (new PasswordRulesTester)->runPasswordRules(required: false);

            expect($rules)->toContain('nullable');
            expect($rules)->not->toContain('required');
        });

        it('exclude confirmed when set to false', function () {
            $rules = (new PasswordRulesTester)->runPasswordRules(confirmed: false);

            expect($rules)->not->toContain('confirmed');
        });

        it('exclude validation when set to false', function () {
            $rules = (new PasswordRulesTester)->runPasswordRules(validate: false, confirmed: false);

            expect($rules)->toBe(['required', 'string', 'max:255']);
        });
    });

    describe('current password rules', function () {
        it('are always the same', function () {
            expect((new PasswordRulesTester)->runCurrentPasswordRules())->toBe(['required', 'string', 'max:255', 'current_password']);
        });
    });

});
