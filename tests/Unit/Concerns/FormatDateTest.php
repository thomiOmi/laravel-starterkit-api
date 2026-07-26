<?php

declare(strict_types=1);

use App\Concerns\FormatDate;
use Carbon\Carbon;

final readonly class FormatDateTester
{
    use FormatDate;

    public function run(DateTimeInterface|string|null $date): ?string
    {
        return $this->formatDate($date);
    }
}

test('formats DateTimeInterface to Y-m-d H:i:s', function () {
    $tester = new FormatDateTester;
    $date = new DateTimeImmutable('2026-07-26 15:30:00');

    expect($tester->run($date))->toBe('2026-07-26 15:30:00');
});

test('formats date string to Y-m-d H:i:s', function () {
    $tester = new FormatDateTester;

    expect($tester->run('2026-07-26 15:30:00'))->toBe('2026-07-26 15:30:00');
});

test('returns null for null input', function () {
    $tester = new FormatDateTester;

    expect($tester->run(null))->toBeNull();
});

test('formats Carbon instance correctly', function () {
    $tester = new FormatDateTester;
    $carbon = Carbon::parse('2026-07-26 15:30:00');

    expect($tester->run($carbon))->toBe('2026-07-26 15:30:00');
});
