<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\error;
use function Laravel\Prompts\table;

#[Signature('module:list')]
#[Description('List all modules and their status')]
class ModuleListCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $modulesPath = base_path('modules');

        if (! File::isDirectory($modulesPath)) {
            error('Modules directory not found.');

            return;
        }

        $modules = File::directories($modulesPath);
        $data = [];

        foreach ($modules as $modulePath) {
            $modulePathString = is_string($modulePath) ? $modulePath : '';
            if ($modulePathString === '') {
                continue;
            }
            $name = basename($modulePathString);

            // Check for ServiceProvider
            $hasProvider = File::exists("{$modulePathString}/Providers/{$name}ServiceProvider.php");

            // Counts
            $controllers = $this->countFilesRecursive("{$modulePathString}/Controllers");
            $actions = $this->countFiles("{$modulePathString}/Actions");
            $payloads = $this->countFilesRecursive("{$modulePathString}/Payloads");
            $filters = $this->countFiles("{$modulePathString}/Filters");
            $migrations = $this->countFiles("{$modulePathString}/Database/Migrations");

            $hasRoutes = File::exists("{$modulePathString}/Routes/V1.php")
                || File::exists("{$modulePathString}/Routes/api.php");

            $data[] = [
                $name,
                $hasProvider ? 'Active' : 'Inactive',
                (string) $controllers,
                (string) $actions,
                (string) $payloads,
                (string) $filters,
                (string) $migrations,
                $hasRoutes ? 'Yes' : 'No',
            ];
        }

        table(
            ['Module Name', 'Status', 'Ctlr', 'Actn', 'Pld', 'Flt', 'Migr', 'Rte'],
            $data
        );
    }

    /**
     * Helper to count files in a directory.
     */
    protected function countFiles(string $path): int
    {
        return File::exists($path) ? count(File::files($path)) : 0;
    }

    /**
     * Helper to count files recursively in a directory.
     */
    protected function countFilesRecursive(string $path): int
    {
        if (! File::exists($path)) {
            return 0;
        }

        return count(File::allFiles($path));
    }
}
