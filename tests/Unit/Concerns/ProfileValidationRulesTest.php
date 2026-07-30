<?php

declare(strict_types=1);

use App\Concerns\ProfileValidationRules;

covers(ProfileValidationRules::class);

use Illuminate\Validation\Rules\Unique;

$tester = new readonly class
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
};

describe('ProfileValidationRules', function () use ($tester) {

    describe('profile rules', function () use ($tester) {
        it('include name and email', function () use ($tester) {
            expect($tester->runProfileRules())->toHaveKeys(['name', 'email']);
        });
    });

    describe('name rules', function () use ($tester) {
        it('are required string max 255', function () use ($tester) {
            expect($tester->runNameRules())->toBe(['required', 'string', 'max:255']);
        });
    });

    describe('email rules', function () use ($tester) {
        it('include required string email max 255', function () use ($tester) {
            $rules = $tester->runEmailRules();

            expect($rules)->toContain('required', 'string', 'email', 'max:255');
        });

        it('include unique rule when unique is true', function () use ($tester) {
            $rules = $tester->runEmailRules();

            expect(collect($rules)->contains(fn (mixed $rule): bool => $rule instanceof Unique))->toBeTrue();
        });

        it('exclude unique rule when unique is false', function () use ($tester) {
            $rules = $tester->runEmailRules(unique: false);

            expect(collect($rules)->contains(fn (mixed $rule): bool => $rule instanceof Unique))->toBeFalse();
        });

        it('ignores userId without unique', function () use ($tester) {
            $rules = $tester->runEmailRules(userId: '01ARZ3NDEKTSV4RRFFQ69G5FAV', unique: false);

            expect($rules)->toBe(['required', 'string', 'email', 'max:255']);
        });
    });

});
