<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\table;

/**
 * Renders a check command result table and returns the matching exit code.
 */
final class CheckReportRenderer
{
    /**
     * @param  array<int, array{check: string, status: string, detail: string}>  $results
     */
    public static function render(array $results, string $noun = 'check'): int
    {
        $rows = array_map(fn (array $result): array => [
            $result['check'],
            $result['status'] === 'pass' ? 'PASS' : 'FAIL',
            $result['detail'],
        ], $results);

        table(['Check', 'Status', 'Detail'], $rows);

        $failed = count(array_filter($results, fn (array $result): bool => $result['status'] === 'fail'));

        if ($failed > 0) {
            error("$failed $noun(s) failed - review the table above.");

            return Command::FAILURE;
        }

        info("All $noun checks passed.");

        return Command::SUCCESS;
    }
}
