<?php

declare(strict_types=1);

use App\Concerns\FormatDate;

covers(FormatDate::class);

$tester = new readonly class
{
    use FormatDate;

    public function run(DateTimeInterface|string|null $date): ?string
    {
        return $this->formatDate($date);
    }
};

describe('FormatDate', function () use ($tester) {

    it('formats DateTimeInterface to Y-m-d H:i:s', function () use ($tester) {
        $date = new DateTimeImmutable('2026-07-26 15:30:00');

        expect($tester->run($date))->toBe('2026-07-26 15:30:00');
    });

    it('reparses and reformats a differently-formatted date string', function () use ($tester) {
        expect($tester->run('26 July 2026 15:30'))->toBe('2026-07-26 15:30:00');
    });

    it('does not shift the instant across timezones when formatting an object', function () use ($tester) {
        $date = new DateTimeImmutable('2026-07-26 22:30:00', new DateTimeZone('Asia/Tokyo'));

        expect($tester->run($date))->toBe('2026-07-26 22:30:00');
    });

    it('returns null for null input', function () use ($tester) {
        expect($tester->run(null))->toBeNull();
    });

    it('returns null for an empty string', function () use ($tester) {
        expect($tester->run(''))->toBeNull();
    });

    it('returns null for an unparseable string', function () use ($tester) {
        expect($tester->run('not a date'))->toBeNull();
    });

});
