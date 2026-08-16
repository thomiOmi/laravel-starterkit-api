<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Config\Repository;
use Nwidart\Modules\Commands\Make\ModuleMakeCommand as NwidartModuleMakeCommand;
use Symfony\Component\Console\Input\InputOption;

use function implode;
use function sprintf;

/**
 * Kit-aware module scaffolder.
 *
 * Extends the nWidart module:make command so generated modules follow the
 * starter kit anatomy: backend-only by default (no controllers, views,
 * package.json, or vite config). A frontend scaffold is added with --frontend
 * (using the framework from config modules.inertia.frontend) or with an
 * explicit framework flag (--vue, --react, --svelte); --no-frontend is the
 * explicit backend-only form, which is also the default. The signature
 * cannot be an attribute here because Laravel's #[Signature] would bypass
 * the parent getArguments() and getOptions() that the nWidart generator
 * relies on.
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
            ['frontend', null, InputOption::VALUE_NONE, 'Generate a module with the default frontend framework from config modules.inertia.frontend.'],
            ['vue', null, InputOption::VALUE_NONE, 'Generate a module with a Vue frontend scaffold.'],
            ['react', null, InputOption::VALUE_NONE, 'Generate a module with a React frontend scaffold.'],
            ['svelte', null, InputOption::VALUE_NONE, 'Generate a module with a Svelte frontend scaffold.'],
        ];
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $framework = $this->option('vue') ? 'vue'
            : ($this->option('react') ? 'react'
            : ($this->option('svelte') ? 'svelte'
            : ($this->option('frontend') ? $this->config->get('modules.inertia.frontend', 'vue') : null)));

        if (is_string($framework)) {
            $this->configureFrontendScaffold($framework);
        }

        return parent::handle();
    }

    /**
     * Add the frontend scaffold stubs to the config for the given framework.
     */
    private function configureFrontendScaffold(string $framework): void
    {
        /** @var array<string, array<string, string>> $stacks */
        $stacks = $this->config->get('modules.frontend.stacks', []);

        if (! isset($stacks[$framework])) {
            throw new \InvalidArgumentException(sprintf(
                'Unsupported frontend stack [%s]. Supported stacks: %s.',
                $framework,
                implode(', ', array_keys($stacks))
            ));
        }

        $this->config->set('modules.stubs.files', array_merge($this->config->get('modules.stubs.files', []), $stacks[$framework]));
    }
}
