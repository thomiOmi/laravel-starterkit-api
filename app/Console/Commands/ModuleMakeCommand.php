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
 * package.json, or vite config). A frontend is added with an explicit
 * framework flag (--vue, --react, --svelte), which delegates to the nWidart
 * Inertia generator using that framework; --no-frontend is the explicit
 * backend-only form, which is also the default. The signature cannot be an
 * attribute here because Laravel's #[Signature] would bypass the parent
 * getArguments() and getOptions() that the nWidart generator relies on.
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
            ['no-frontend', null, InputOption::VALUE_NONE, 'Do not generate a frontend scaffold (default).'],
            ['vue', null, InputOption::VALUE_NONE, 'Generate an Inertia module with Vue pages.'],
            ['react', null, InputOption::VALUE_NONE, 'Generate an Inertia module with React pages.'],
            ['svelte', null, InputOption::VALUE_NONE, 'Generate an Inertia module with Svelte pages.'],
        ];
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $framework = $this->option('vue') ? 'vue'
            : ($this->option('react') ? 'react'
            : ($this->option('svelte') ? 'svelte' : null));

        if (! is_string($framework)) {
            return parent::handle();
        }

        $previous = $this->config->get('modules.inertia.frontend');

        $this->input->setOption('inertia', true);
        $this->config->set('modules.inertia.frontend', $framework);

        try {
            return parent::handle();
        } finally {
            $this->config->set('modules.inertia.frontend', $previous);
        }
    }
}
