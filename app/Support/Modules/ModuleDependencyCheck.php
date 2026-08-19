<?php

declare(strict_types=1);

namespace App\Support\Modules;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Module;

/**
 * Validate module dependency declarations in each module.json "requires"
 * field and the dependency graph they form.
 *
 * Checks that every declared dependency is an installed and enabled module,
 * and that the requires graph contains no cycles. Each check returns a
 * 'pass' or 'fail' verdict so the caller can surface results in a CLI
 * table or CI gate.
 */
final readonly class ModuleDependencyCheck
{
    public function __construct(public RepositoryInterface $modules) {}

    /**
     * @return array<int, array{check: string, status: string, detail: string}>
     */
    public function __invoke(): array
    {
        $declared = [];

        foreach ($this->modules() as $module) {
            $declared[$module->getName()] = $this->declaredDependencies($module);
        }

        $rows = [];

        foreach ($declared as $module => $dependencies) {
            foreach ($dependencies as $dependency) {
                $installed = $this->modules->find($dependency);

                if ($installed === null) {
                    $rows[] = [
                        'check' => "{$module} -> {$dependency}",
                        'status' => 'fail',
                        'detail' => "{$dependency} is not an installed module",
                    ];
                } elseif (! $installed->isEnabled()) {
                    $rows[] = [
                        'check' => "{$module} -> {$dependency}",
                        'status' => 'fail',
                        'detail' => "{$dependency} is disabled",
                    ];
                }
            }
        }

        foreach (array_unique($this->findCycles($declared)) as $cycle) {
            $rows[] = [
                'check' => 'circular dependency',
                'status' => 'fail',
                'detail' => $cycle,
            ];
        }

        if ($rows === []) {
            $rows[] = [
                'check' => 'module dependencies',
                'status' => 'pass',
                'detail' => 'All declared dependencies exist, are enabled, and the requires graph is acyclic',
            ];
        }

        return $rows;
    }

    /**
     * The repository interface types all() as mixed, so narrow it at our
     * boundary to a list of module instances.
     *
     * @return list<Module>
     */
    private function modules(): array
    {
        $modules = $this->modules->all();

        if (! is_array($modules)) {
            return [];
        }

        return array_values(array_filter(
            $modules,
            static fn (mixed $module): bool => $module instanceof Module
        ));
    }

    /**
     * @return list<string>
     */
    private function declaredDependencies(Module $module): array
    {
        $requires = $module->get('requires', []);

        return is_array($requires)
            ? array_values(array_filter($requires, is_string(...)))
            : [];
    }

    /**
     * @param  array<string, list<string>>  $graph
     * @return list<string>
     */
    private function findCycles(array $graph): array
    {
        $cycles = [];

        foreach (array_keys($graph) as $module) {
            $cycle = $this->detectCycle($graph, $module, []);

            if ($cycle !== null) {
                $cycles[] = implode(' -> ', $cycle);
            }
        }

        return $cycles;
    }

    /**
     * @param  array<string, list<string>>  $graph
     * @param  list<string>  $path
     * @return list<string>|null
     */
    private function detectCycle(array $graph, string $node, array $path): ?array
    {
        if (in_array($node, $path, true)) {
            return [...$path, $node];
        }

        foreach ($graph[$node] ?? [] as $dependency) {
            $cycle = $this->detectCycle($graph, $dependency, [...$path, $node]);

            if ($cycle !== null) {
                return $cycle;
            }
        }

        return null;
    }
}
