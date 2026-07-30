<?php

declare(strict_types=1);

use App\Concerns\FormatDate;

covers(FormatDate::class);

use Carbon\Carbon;

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

    it('formats date string to Y-m-d H:i:s', function () use ($tester) {
        expect($tester->run('2026-07-26 15:30:00'))->toBe('2026-07-26 15:30:00');
    });

    it('returns null for null input', function () use ($tester) {
        expect($tester->run(null))->toBeNull();
    });

    it('formats Carbon instance correctly', function () use ($tester) {
        $carbon = Carbon::parse('2026-07-26 15:30:00');

        expect($tester->run($carbon))->toBe('2026-07-26 15:30:00');
    });

});
