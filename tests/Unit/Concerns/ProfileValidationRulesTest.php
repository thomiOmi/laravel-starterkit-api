<?php

declare(strict_types=1);

use App\Concerns\ProfileValidationRules;

covers(ProfileValidationRules::class);

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

describe('ProfileValidationRules', function () {

    describe('profile rules', function () {
        it('include name and email', function () {
            expect((new ProfileRulesTester)->runProfileRules())->toHaveKeys(['name', 'email']);
        });
    });

    describe('name rules', function () {
        it('are required string max 255', function () {
            expect((new ProfileRulesTester)->runNameRules())->toBe(['required', 'string', 'max:255']);
        });
    });

    describe('email rules', function () {
        it('include required string email max 255', function () {
            $rules = (new ProfileRulesTester)->runEmailRules();

            expect($rules)->toContain('required', 'string', 'email', 'max:255');
        });

        it('include unique rule when unique is true', function () {
            $rules = (new ProfileRulesTester)->runEmailRules();

            expect(collect($rules)->contains(fn (mixed $rule): bool => $rule instanceof Unique))->toBeTrue();
        });

        it('exclude unique rule when unique is false', function () {
            $rules = (new ProfileRulesTester)->runEmailRules(unique: false);

            expect(collect($rules)->contains(fn (mixed $rule): bool => $rule instanceof Unique))->toBeFalse();
        });

        it('ignores userId without unique', function () {
            $rules = (new ProfileRulesTester)->runEmailRules(userId: '01ARZ3NDEKTSV4RRFFQ69G5FAV', unique: false);

            expect($rules)->toBe(['required', 'string', 'email', 'max:255']);
        });
    });

});
