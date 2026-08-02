<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

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
        if (! File::isDirectory(config()->string('filesystems.disks.modules.root'))) {
            error('Modules directory not found.');

            return;
        }

        $modules = Storage::disk('modules')->directories('');
        $data = [];

        foreach ($modules as $module) {
            if (! is_string($module)) {
                continue;
            }

            $name = $module;

            // Check for ServiceProvider
            $hasProvider = Storage::disk('modules')->exists("{$name}/Providers/{$name}ServiceProvider.php");

            // Counts
            $controllers = $this->countFilesRecursive("{$name}/Controllers");
            $actions = $this->countFiles("{$name}/Actions");
            $payloads = $this->countFilesRecursive("{$name}/Payloads");
            $filters = $this->countFiles("{$name}/Filters");
            $migrations = $this->countFiles("{$name}/Database/Migrations");

            $hasRoutes = Storage::disk('modules')->exists("{$name}/Routes/V1.php")
                || Storage::disk('modules')->exists("{$name}/Routes/api.php");

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
        return Storage::disk('modules')->directoryExists($path) ? count(Storage::disk('modules')->files($path)) : 0;
    }

    /**
     * Helper to count files recursively in a directory.
     */
    protected function countFilesRecursive(string $path): int
    {
        return Storage::disk('modules')->directoryExists($path) ? count(Storage::disk('modules')->allFiles($path)) : 0;
    }
}
