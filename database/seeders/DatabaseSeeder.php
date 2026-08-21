<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Module;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach ($this->activeModuleSeeders() as $seeder) {
            $this->call($seeder);
        }
    }

    /**
     * Resolve the seeder class for every enabled module that ships one.
     *
     * The module seeder follows the {Module}Seeder convention (see ADR-0020),
     * with {Module}DatabaseSeeder accepted as a fallback for modules scaffolded
     * with make:module.
     *
     * @return list<class-string<Seeder>>
     */
    private function activeModuleSeeders(): array
    {
        $seeders = [];

        foreach (app(RepositoryInterface::class)->allEnabled() as $module) {
            if (! $module instanceof Module) {
                continue;
            }

            $studly = $module->getStudlyName();
            $prefix = sprintf('Modules\\%s\\Database\\Seeders\\%s', $studly, $studly);

            foreach (['Seeder', 'DatabaseSeeder'] as $suffix) {
                $seeder = $prefix.$suffix;

                if (class_exists($seeder) && is_subclass_of($seeder, Seeder::class)) {
                    $seeders[] = $seeder;

                    break;
                }
            }
        }

        return $seeders;
    }
}
