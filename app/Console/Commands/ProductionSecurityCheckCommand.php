<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\CheckReportRenderer;
use App\Support\Production\ProductionSecurityCheck;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Help;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Usage;
use Illuminate\Console\Command;

#[Signature('security:check')]
#[Description('Validate production environment configuration.')]
#[Help('Check application logs, database, mail, queue, cache, routes, schedule, and SSL for common production misconfigurations.')]
#[Usage('security:check')]
class ProductionSecurityCheckCommand extends Command
{
    public function handle(ProductionSecurityCheck $check): int
    {
        return CheckReportRenderer::render($check(), 'production security');
    }
}
