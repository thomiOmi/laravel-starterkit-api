<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Production\ProductionSecurityCheck;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\table;

#[Signature('security:check')]
#[Description('Validate production environment configuration.')]
class ProductionSecurityCheckCommand extends Command
{
    public function handle(ProductionSecurityCheck $check): int
    {
        $results = $check();

        $rows = array_map(fn (array $result): array => [
            $result['check'],
            $result['status'] === 'pass' ? 'PASS' : 'FAIL',
            $result['detail'],
        ], $results);

        table(['Check', 'Status', 'Detail'], $rows);

        $failed = count(array_filter($results, fn (array $r): bool => $r['status'] === 'fail'));

        if ($failed > 0) {
            $this->newLine();
            error("{$failed} check(s) failed — review the warnings above.");

            return self::FAILURE;
        }

        $this->newLine();
        info('All production security checks passed.');

        return self::SUCCESS;
    }
}
