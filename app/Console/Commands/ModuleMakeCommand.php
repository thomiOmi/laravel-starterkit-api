<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Config\Repository;
use Nwidart\Modules\Commands\Make\ModuleMakeCommand as NwidartModuleMakeCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * Kit-aware module scaffolder.
 *
 * Extends the nWidart module:make command so generated modules follow the
 * starter kit anatomy: backend-only by default (no controllers, views,
 * package.json, or vite config), with an optional Vite frontend scaffold
 * behind the --frontend flag. The signature cannot be an attribute here
 * because Laravel's #[Signature] would bypass the parent getArguments()
 * and getOptions() that the nWidart generator relies on.
 */
class ModuleMakeCommand extends NwidartModuleMakeCommand
{
    public function __construct(private readonly Repository $config)
    {
        parent::__construct();
    }

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new module following the starter kit conventions.';

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ...parent::getOptions(),
            ['frontend', null, InputOption::VALUE_NONE, 'Generate a module with a Vite frontend scaffold. Default is backend-only.'],
        ];
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->configureFrontendScaffold();

        return parent::handle();
    }

    /**
     * Add the frontend scaffold stubs to the config when the --frontend flag is set.
     */
    private function configureFrontendScaffold(): void
    {
        if (! $this->option('frontend')) {
            return;
        }

        $this->config->set('modules.stubs.files', array_merge($this->config->get('modules.stubs.files', []), [
            'package' => 'package.json',
            'vite' => 'vite.config.js',
            'assets/js/app' => 'resources/js/app.js',
            'assets/css/app' => 'resources/css/app.css',
        ]));
    }
}
