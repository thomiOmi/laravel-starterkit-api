<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class ModuleTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:test {name : The name of the module} {--filter= : Filter tests by name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run tests for a specific module';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = ucfirst($this->argument('name'));
        $modulePath = base_path("modules/{$name}");
        $testPath = "{$modulePath}/Tests";

        if (! File::isDirectory($modulePath)) {
            $this->error("Module [{$name}] does not exist.");

            return 1;
        }

        if (! File::isDirectory($testPath)) {
            // Check if there are nested tests in subdirectories
            $this->warn("No [Tests] directory found directly in [{$name}] module.");
            $this->info('Scanning for any .php files in the module...');
        }

        $this->info("Running tests for module: {$name}...");

        $command = [
            PHP_BINARY,
            'vendor/bin/pest',
            "modules/{$name}",
            '--compact',
        ];

        if ($this->option('filter')) {
            $command[] = '--filter';
            $command[] = $this->option('filter');
        }

        $process = new Process($command, base_path(), null, null, null);
        $process->setTty(Process::isTtySupported());

        return $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });
    }
}
