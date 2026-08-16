<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Config\Repository;
use Nwidart\Modules\Commands\Make\ModuleMakeCommand as NwidartModuleMakeCommand;
use Symfony\Component\Console\Input\InputOption;

use function implode;
use function is_string;
use function sprintf;

/**
 * Kit-aware module scaffolder.
 *
 * Extends the nWidart module:make command so generated modules follow the
 * starter kit anatomy: backend-only by default (no controllers, views,
 * package.json, or vite config), with an optional frontend scaffold behind
 * the --frontend flag. The frontend stack is chosen with --frontend=vite
 * (or any stack registered in config/modules.php); passing the flag without
 * a value uses the configured default stack. The signature cannot be an
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
            ['frontend', null, InputOption::VALUE_OPTIONAL, 'Generate a module with a frontend scaffold. Use --frontend=stack to pick a stack, or omit the value to use the configured default.', null],
        ];
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->input->hasParameterOption('--frontend')) {
            $this->configureFrontendScaffold();
        }

        return parent::handle();
    }

    /**
     * Add the frontend scaffold stubs to the config when the --frontend flag is set.
     */
    private function configureFrontendScaffold(): void
    {
        /** @var array<string, array<string, string>> $stacks */
        $stacks = $this->config->get('modules.frontend.stacks', []);

        $stack = $this->option('frontend');

        if (! is_string($stack) || $stack === '') {
            $stack = $this->config->get('modules.frontend.default', 'vite');
        }

        if (! isset($stacks[$stack])) {
            throw new \InvalidArgumentException(sprintf(
                'Unsupported frontend stack [%s]. Supported stacks: %s.',
                $stack,
                implode(', ', array_keys($stacks))
            ));
        }

        $this->config->set('modules.stubs.files', array_merge($this->config->get('modules.stubs.files', []), $stacks[$stack]));
    }
}
