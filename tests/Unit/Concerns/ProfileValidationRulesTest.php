<?php

declare(strict_types=1);

use App\Concerns\ProfileValidationRules;
use Illuminate\Validation\Rules\Unique;

covers(ProfileValidationRules::class);

$makeTester = fn (): object => new class
{
    use ProfileValidationRules;

    /** @var array<string, mixed> */
    private array $attributes = [];

    /** @param  array<string, mixed>  $data */
    public function merge(array $data): void
    {
        $this->attributes = [...$this->attributes, ...$data];
    }

    public function input(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function runNormalizeEmail(array $data): array
    {
        $this->merge($data);
        $this->normalizeEmail();

        return $this->attributes;
    }

    /** @return array<string, array<int, Unique|string>> */
    public function runProfileRules(?string $userId = null, bool $unique = true): array
    {
        return $this->profileRules($userId, $unique);
    }

    /** @return array<int, string> */
    public function runNameRules(): array
    {
        return $this->nameRules();
    }

    /** @return array<int, Unique|string> */
    public function runEmailRules(?string $userId = null, bool $unique = true): array
    {
        return $this->emailRules($userId, $unique);
    }
};

describe('ProfileValidationRules', function () use ($makeTester) {

    describe('profile rules', function () use ($makeTester) {
        it('include name and email', function () use ($makeTester) {
            expect($makeTester()->runProfileRules())->toHaveKeys(['name', 'email']);
        });
    });

    describe('name rules', function () use ($makeTester) {
        it('are required string max 255', function () use ($makeTester) {
            expect($makeTester()->runNameRules())->toBe(['required', 'string', 'max:255']);
        });
    });

    describe('email rules', function () use ($makeTester) {
        it('include required string email max 255', function () use ($makeTester) {
            $rules = $makeTester()->runEmailRules();

            expect($rules)->toContain('required', 'string', 'email', 'max:255');
        });

        it('include unique rule when unique is true', function () use ($makeTester) {
            $rules = $makeTester()->runEmailRules();

            expect(collect($rules)->contains(fn (mixed $rule): bool => $rule instanceof Unique))->toBeTrue();
        });

        it('exclude unique rule when unique is false', function () use ($makeTester) {
            $rules = $makeTester()->runEmailRules(unique: false);

            expect(collect($rules)->contains(fn (mixed $rule): bool => $rule instanceof Unique))->toBeFalse();
        });

        it('ignores userId without unique', function () use ($makeTester) {
            $rules = $makeTester()->runEmailRules(userId: '01ARZ3NDEKTSV4RRFFQ69G5FAV', unique: false);

            expect($rules)->toBe(['required', 'string', 'email', 'max:255']);
        });
    });
});

describe('normalizeEmail', function () use ($makeTester) {
    it('trims and lowercases the email input', function () use ($makeTester) {
        expect($makeTester()->runNormalizeEmail(['email' => '  Jane@Example.COM  ']))
            ->toBe(['email' => 'jane@example.com']);
    });

    it('leaves non-string input untouched', function () use ($makeTester) {
        expect($makeTester()->runNormalizeEmail([]))->toBeEmpty();
    });
});
