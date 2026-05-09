<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ModuleList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all modules and their status';

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
            $modulePath = (string) $modulePath;
            $name = basename($modulePath);

            // Check for ServiceProvider
            $hasProvider = File::exists("{$modulePath}/Providers/{$name}ServiceProvider.php");

            // Counts
            $controllers = $this->countFiles("{$modulePath}/Controllers");
            $actions = $this->countFiles("{$modulePath}/Actions");
            $services = $this->countFiles("{$modulePath}/Services");
            $dtos = $this->countFiles("{$modulePath}/DTOs");
            $migrations = $this->countFiles("{$modulePath}/Database/Migrations");

            $hasRoutes = File::exists("{$modulePath}/Routes/api.php");

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
