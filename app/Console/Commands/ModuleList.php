<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('module:list')]
#[Description('List all modules and their status')]
class ModuleList extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $modulesPath = base_path('modules');

        if (! File::isDirectory($modulesPath)) {
            $this->error('Modules directory not found.');

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
            $controllers = $this->countFiles("{$modulePathString}/Controllers");
            $actions = $this->countFiles("{$modulePathString}/Actions");
            $services = $this->countFiles("{$modulePathString}/Services");
            $dtos = $this->countFiles("{$modulePathString}/DTOs");
            $migrations = $this->countFiles("{$modulePathString}/Database/Migrations");

            $hasRoutes = File::exists("{$modulePathString}/Routes/api.php");

            $data[] = [
                $name,
                $hasProvider ? '<fg=green>Active</>' : '<fg=red>Inactive</>',
                $controllers,
                $actions,
                $services,
                $dtos,
                $migrations,
                $hasRoutes ? '<fg=green>Yes</>' : '<fg=red>No</>',
            ];
        }

        $this->table(
            ['Module Name', 'Status', 'Ctlr', 'Actn', 'Svc', 'DTO', 'Migr', 'Rte'],
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
}
