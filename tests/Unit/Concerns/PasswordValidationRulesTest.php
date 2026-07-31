<?php

declare(strict_types=1);

use App\Concerns\PasswordValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Password;

covers(PasswordValidationRules::class);

$tester = new readonly class
{
    use PasswordValidationRules;

    /** @return array<int, ValidationRule|Password|string> */
    public function runPasswordRules(bool $required = true, bool $confirmed = true, bool $validate = true): array
    {
        return $this->passwordRules($required, $confirmed, $validate);
    }

    /** @return array<int, string> */
    public function runCurrentPasswordRules(): array
    {
        return $this->currentPasswordRules();
    }
};

describe('PasswordValidationRules', function () use ($tester) {

    describe('password rules', function () use ($tester) {
        it('include required by default', function () use ($tester) {
            $rules = $tester->runPasswordRules();

            expect($rules)->toContain('required', 'string', 'confirmed');
        });

        it('exclude required when set to false', function () use ($tester) {
            $rules = $tester->runPasswordRules(required: false);

            expect($rules)->toContain('nullable')->not->toContain('required');
        });

        it('exclude confirmed when set to false', function () use ($tester) {
            $rules = $tester->runPasswordRules(confirmed: false);

            expect($rules)->not->toContain('confirmed');
        });

        it('exclude validation when set to false', function () use ($tester) {
            $rules = $tester->runPasswordRules(confirmed: false, validate: false);

            expect($rules)->toBe(['required', 'string', 'max:255']);
        });
    });

    describe('current password rules', function () use ($tester) {
        it('are always the same', function () use ($tester) {
            expect($tester->runCurrentPasswordRules())->toBe(['required', 'string', 'max:255', 'current_password']);
        });
    });

});
