<?php

declare(strict_types=1);

use App\Concerns\ProfileValidationRules;
use Illuminate\Validation\Rules\Email;
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

    /** @return array<string, array<int, Email|Unique|string>> */
    public function runProfileRules(?string $userId = null, bool $unique = true): array
    {
        return $this->profileRules($userId, $unique);
    }

    /** @return array<int, string> */
    public function runNameRules(): array
    {
        return $this->nameRules();
    }

    /** @return array<int, Email|Unique|string> */
    public function runEmailRules(?string $userId = null, bool $unique = true): array
    {
        return $this->emailRules($userId, $unique);
    }
};

describe('ProfileValidationRules', function () use ($makeTester): void {

    describe('profile rules', function () use ($makeTester): void {
        it('include name and email', function () use ($makeTester): void {
            expect($makeTester()->runProfileRules())->toHaveKeys(['name', 'email']);
        });
    });

    describe('name rules', function () use ($makeTester): void {
        it('are required string max 255', function () use ($makeTester): void {
            expect($makeTester()->runNameRules())->toBe(['required', 'string', 'max:255']);
        });
    });

    describe('email rules', function () use ($makeTester): void {
        it('bail first and check length before the email rule', function () use ($makeTester): void {
            $rules = $makeTester()->runEmailRules();
            $indexes = array_keys(array_filter(
                $rules,
                fn (mixed $rule): bool => $rule instanceof Email || $rule === 'max:255',
            ));

            expect(array_slice($rules, 0, 4))->toBe(['bail', 'required', 'string', 'max:255'])
                ->and($rules[$indexes[0] ?? -1])->toBe('max:255')
                ->and($rules[$indexes[1] ?? -1])->toBeInstanceOf(Email::class);
        });

        it('uses an RFC and spoof-check rule when unique is true', function () use ($makeTester): void {
            $rules = $makeTester()->runEmailRules();
            $emailRule = array_find($rules, fn ($rule) => $rule instanceof Email);

            expect($emailRule)->toBeInstanceOf(Email::class)
                ->and($emailRule?->rfcCompliant)->toBeTrue()
                ->and($emailRule?->preventSpoofing)->toBeTrue()
                ->and($emailRule?->validateMxRecord)->toBeFalse()
                ->and($rules)->not->toContain('ascii')
                ->and($rules)->not->toContain('email');
        });

        it('include unique rule when unique is true', function () use ($makeTester): void {
            $rules = $makeTester()->runEmailRules();

            expect(collect($rules)->contains(fn (mixed $rule): bool => $rule instanceof Unique))->toBeTrue();
        });

        it('exclude unique rule when unique is false', function () use ($makeTester): void {
            $rules = $makeTester()->runEmailRules(unique: false);

            expect(collect($rules)->contains(fn (mixed $rule): bool => $rule instanceof Unique))->toBeFalse();
        });

        it('keeps plain email rules when unique is false', function () use ($makeTester): void {
            $rules = $makeTester()->runEmailRules(userId: '01ARZ3NDEKTSV4RRFFQ69G5FAV', unique: false);

            expect($rules)->toBe(['bail', 'required', 'string', 'max:255', 'email']);
        });
    });
});

describe('normalizeEmail', function () use ($makeTester): void {
    it('trims and lowercases the email input', function () use ($makeTester): void {
        expect($makeTester()->runNormalizeEmail(['email' => '  Jane@Example.COM  ']))
            ->toBe(['email' => 'jane@example.com']);
    });

    it('lowercases non-ASCII letters', function () use ($makeTester): void {
        expect($makeTester()->runNormalizeEmail(['email' => '  '."\u{00C9}MILE@Example.COM".'  ']))
            ->toBe(['email' => "\u{00E9}mile@example.com"]);
    });

    it('leaves non-string input untouched', function () use ($makeTester): void {
        expect($makeTester()->runNormalizeEmail([]))->toBeEmpty();
    });
});
