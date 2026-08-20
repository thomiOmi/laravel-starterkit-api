<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\CheckReportRenderer;
use App\Support\Modules\ModuleDependencyCheck;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Help;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Usage;
use Illuminate\Console\Command;

#[Signature('module:check-dependencies')]
#[Description('Validate module dependency declarations and the requires graph.')]
#[Help('Check that every dependency declared in module.json "requires" is an installed and enabled module, and that the requires graph is acyclic.')]
#[Usage('module:check-dependencies')]
class ModuleDependencyCheckCommand extends Command
{
    public function handle(ModuleDependencyCheck $check): int
    {
        return CheckReportRenderer::render($check(), 'module dependency');
    }
}
