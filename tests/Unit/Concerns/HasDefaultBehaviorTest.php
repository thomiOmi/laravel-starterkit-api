<?php

declare(strict_types=1);

use App\Concerns\HasDefaultBehavior;

covers(HasDefaultBehavior::class);

$tester = new readonly class
{
    use HasDefaultBehavior;

    public function getKeyName(): string
    {
        return 'id';
    }

    public function usesUniqueIds(): bool
    {
        return true;
    }

    public function newId(): string
    {
        return $this->newUniqueId();
    }

    public function keyType(): string
    {
        return $this->getKeyType();
    }

    public function serialize(DateTimeInterface $date): string
    {
        return $this->serializeDate($date);
    }
};

describe('HasDefaultBehavior', function () use ($tester) {

    it('generates lowercase ULID identifiers', function () use ($tester) {
        expect($tester->newId())->toMatch('/^[0-7][0-9a-hjkmnp-tv-z]{25}$/');
    });

    it('uses string primary keys', function () use ($tester) {
        expect($tester->keyType())->toBe('string');
    });

    it('serializes dates in Y-m-d H:i:s format', function () use ($tester) {
        expect($tester->serialize(new DateTimeImmutable('2026-07-26 15:30:00')))->toBe('2026-07-26 15:30:00');
    });

    it('does not shift dates across timezones when serializing', function () use ($tester) {
        $date = new DateTimeImmutable('2026-07-26 15:30:00', new DateTimeZone('Asia/Jakarta'));

        expect($tester->serialize($date))->toBe('2026-07-26 15:30:00');
    });

});
