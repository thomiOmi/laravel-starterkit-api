<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Modules\ModuleDependencyCheck;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Help;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Usage;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\table;

#[Signature('module:check-dependencies')]
#[Description('Validate module dependency declarations and the requires graph.')]
#[Help('Check that every dependency declared in module.json "requires" is an installed and enabled module, and that the requires graph is acyclic.')]
#[Usage('module:check-dependencies')]
class ModuleDependencyCheckCommand extends Command
{
    public function handle(ModuleDependencyCheck $check): int
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
            error("{$failed} module dependency check(s) failed — review the table above.");

            return self::FAILURE;
        }

        $this->newLine();
        info('All module dependency checks passed.');

        return self::SUCCESS;
    }
}
