<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Production\ProductionSecurityCheck;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('security:check')]
#[Description('Validate production environment configuration.')]
class ProductionSecurityCheckCommand extends Command
{
    public function handle(ProductionSecurityCheck $check): int
    {
        $results = $check();

        $rows = array_map(fn (array $result): array => [
            $result['check'],
            $result['status'] === 'pass' ? '<info>PASS</info>' : '<error>FAIL</error>',
            $result['detail'],
        ], $results);

        $this->table(['Check', 'Status', 'Detail'], $rows);

        $failed = count(array_filter($results, fn (array $r): bool => $r['status'] === 'fail'));

        if ($failed > 0) {
            $this->newLine();
            $this->error("{$failed} check(s) failed — review the warnings above.");

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('All production security checks passed.');

        return self::SUCCESS;
    }
}
