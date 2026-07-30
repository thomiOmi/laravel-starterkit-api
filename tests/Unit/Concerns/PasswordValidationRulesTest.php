<?php

declare(strict_types=1);

use App\Concerns\PasswordValidationRules;

covers(PasswordValidationRules::class);

$tester = new readonly class
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
};

describe('PasswordValidationRules', function () use ($tester) {

    describe('password rules', function () use ($tester) {
        it('include required by default', function () use ($tester) {
            $rules = $tester->runPasswordRules();

            expect($rules)->toContain('required', 'string', 'confirmed');
        });

        it('exclude required when set to false', function () use ($tester) {
            $rules = $tester->runPasswordRules(required: false);

            expect($rules)->toContain('nullable');
            expect($rules)->not->toContain('required');
        });

        it('exclude confirmed when set to false', function () use ($tester) {
            $rules = $tester->runPasswordRules(confirmed: false);

            expect($rules)->not->toContain('confirmed');
        });

        it('exclude validation when set to false', function () use ($tester) {
            $rules = $tester->runPasswordRules(validate: false, confirmed: false);

            expect($rules)->toBe(['required', 'string', 'max:255']);
        });
    });

    describe('current password rules', function () use ($tester) {
        it('are always the same', function () use ($tester) {
            expect($tester->runCurrentPasswordRules())->toBe(['required', 'string', 'max:255', 'current_password']);
        });
    });

});
