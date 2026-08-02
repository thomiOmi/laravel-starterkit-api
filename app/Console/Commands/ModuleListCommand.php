<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\table;

#[Signature('module:list')]
#[Description('List all modules and their status')]
class ModuleListCommand extends Command
{
    protected const array TRACKED_DIRS = [
        'controllers' => ['path' => 'Controllers', 'recursive' => true],
        'actions' => ['path' => 'Actions', 'recursive' => false],
        'payloads' => ['path' => 'Payloads', 'recursive' => true],
        'filters' => ['path' => 'Filters', 'recursive' => false],
        'migrations' => ['path' => 'Database/Migrations', 'recursive' => false],
    ];

    public function handle(): void
    {
        try {
            $disk = Storage::disk('modules');

            /** @var list<string> $modules */
            $modules = $disk->directories('');
        } catch (\Throwable $e) {
            error("Modules directory not accessible: {$e->getMessage()}");

            return;
        }

        if ($modules === []) {
            info('No modules found.');

            return;
        }

        sort($modules, SORT_NATURAL | SORT_FLAG_CASE);

        try {
            /** @var list<string> $allFiles */
            $allFiles = $disk->allFiles('');
        } catch (\Throwable $e) {
            error("Failed to read module files: {$e->getMessage()}");

            return;
        }

        $data = [];

        foreach ($modules as $name) {
            $files = array_values(array_filter(
                $allFiles,
                fn (string $file): bool => Str::startsWith($file, "{$name}/")
            ));

            $hasProvider = in_array("{$name}/Providers/{$name}ServiceProvider.php", $files, true);

            $hasRoutes = in_array("{$name}/Routes/V1.php", $files, true)
                || in_array("{$name}/Routes/api.php", $files, true);

            $counts = [];
            foreach (self::TRACKED_DIRS as $key => $config) {
                $counts[$key] = $this->countIn($files, "{$name}/{$config['path']}/", $config['recursive']);
            }

            $data[] = [
                $name,
                $hasProvider ? 'Active' : 'Inactive',
                (string) $counts['controllers'],
                (string) $counts['actions'],
                (string) $counts['payloads'],
                (string) $counts['filters'],
                (string) $counts['migrations'],
                $hasRoutes ? 'Yes' : 'No',
            ];
        }

        /** @var array<int, array<int, string>> $data */
        table(
            ['Module Name', 'Status', 'Controllers', 'Actions', 'Payloads', 'Filters', 'Migrations', 'Routes'],
            $data
        );
    }

    /**
     * Count files under $prefix from an already-fetched file list.
     * When $recursive is false, only counts files directly inside $prefix (no subfolders).
     *
     * @param  list<string>  $files
     */
    protected function countIn(array $files, string $prefix, bool $recursive): int
    {
        $matches = array_filter($files, fn (string $file) => Str::startsWith($file, $prefix));

        if ($recursive) {
            return count($matches);
        }

        return count(array_filter(
            $matches,
            fn (string $file) => ! str_contains(Str::after($file, $prefix), '/')
        ));
    }
}
