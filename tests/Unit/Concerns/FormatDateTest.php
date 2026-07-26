<?php

declare(strict_types=1);

use App\Concerns\FormatDate;

covers(FormatDate::class);

use Carbon\Carbon;

final readonly class FormatDateTester
{
    use FormatDate;

    public function run(DateTimeInterface|string|null $date): ?string
    {
        return $this->formatDate($date);
    }
}

describe('FormatDate', function () {

    it('formats DateTimeInterface to Y-m-d H:i:s', function () {
        $date = new DateTimeImmutable('2026-07-26 15:30:00');

        expect((new FormatDateTester)->run($date))->toBe('2026-07-26 15:30:00');
    });

    it('formats date string to Y-m-d H:i:s', function () {
        expect((new FormatDateTester)->run('2026-07-26 15:30:00'))->toBe('2026-07-26 15:30:00');
    });

    it('returns null for null input', function () {
        expect((new FormatDateTester)->run(null))->toBeNull();
    });

    it('formats Carbon instance correctly', function () {
        $carbon = Carbon::parse('2026-07-26 15:30:00');

        expect((new FormatDateTester)->run($carbon))->toBe('2026-07-26 15:30:00');
    });

});
