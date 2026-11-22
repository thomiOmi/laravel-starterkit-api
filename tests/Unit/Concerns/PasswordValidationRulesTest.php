<?php

declare(strict_types=1);

use App\Concerns\PasswordValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\ConditionalRules;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Confirmed;
use Illuminate\Validation\Rules\CurrentPassword;
use Illuminate\Validation\Rules\Password;

covers(PasswordValidationRules::class);

$tester = new readonly class
{
    use PasswordValidationRules;

    /** @return array<int, ConditionalRules|Confirmed|CurrentPassword|ValidationRule|Password|string> */
    public function runPasswordRules(bool $required = true, bool $confirmed = true, bool $validate = true): array
    {
        return $this->passwordRules($required, $confirmed, $validate);
    }

    /** @return array<int, CurrentPassword|ValidationRule|string> */
    public function runCurrentPasswordRules(): array
    {
        return $this->currentPasswordRules();
    }
};

/**
 * Expand the rule closures the way the validator does.
 *
 * @param  array<int, mixed>  $rules
 * @return array<int, mixed>
 */
$compile = function (array $rules): array {
    /** @var object{rules: array<string, array<int, mixed>>} $compiled */
    $compiled = Rule::compile('password', $rules, []);

    return $compiled->rules['password'];
};

describe('PasswordValidationRules', function () use ($tester, $compile): void {

    describe('password rules', function () use ($tester, $compile): void {
        it('bail first and order cheap rules before expensive ones', function () use ($tester, $compile): void {
            $rules = $compile($tester->runPasswordRules());

            expect(array_slice($rules, 0, 4))->toBe(['bail', 'required', 'string', 'max:255'])
                ->and($rules[4])->toBeInstanceOf(Confirmed::class)
                ->and($rules[5])->toBeInstanceOf(Password::class);
        });

        it('include required by default', function () use ($tester, $compile): void {
            expect($compile($tester->runPasswordRules()))->toContain('required', 'string');
        });

        it('exclude required when set to false', function () use ($tester, $compile): void {
            expect($compile($tester->runPasswordRules(required: false)))
                ->toContain('nullable')
                ->not->toContain('required');
        });

        it('exclude confirmed when set to false', function () use ($tester, $compile): void {
            $rules = $compile($tester->runPasswordRules(confirmed: false));

            expect(collect($rules)->contains(fn (mixed $rule): bool => $rule instanceof Confirmed))->toBeFalse();
        });

        it('exclude validation when set to false', function () use ($tester, $compile): void {
            $rules = $compile($tester->runPasswordRules(confirmed: false, validate: false));

            expect($rules)->toBe(['bail', 'required', 'string', 'max:255']);
        });
    });

    describe('current password rules', function () use ($tester): void {
        it('are always the same and bail first', function () use ($tester): void {
            /** @var object{rules: array<string, array<int, mixed>>} $compiled */
            $compiled = Rule::compile('current_password', $tester->runCurrentPasswordRules(), []);
            $rules = $compiled->rules['current_password'];

            expect(array_slice($rules, 0, 4))->toBe(['bail', 'required', 'string', 'max:255'])
                ->and($rules[4])->toBeInstanceOf(CurrentPassword::class)
                ->and($rules)->toHaveCount(5);
        });
    });

});
