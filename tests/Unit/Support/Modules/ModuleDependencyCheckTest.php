<?php

declare(strict_types=1);

use App\Support\Modules\ModuleDependencyCheck;
use Nwidart\Modules\Contracts\RepositoryInterface;

covers(ModuleDependencyCheck::class);

beforeEach(function (): void {
    clearFixtureModules();
});

afterEach(function (): void {
    clearFixtureModules();
});

function dependencyCheck(): ModuleDependencyCheck
{
    bindFixtureModulePaths();

    return new ModuleDependencyCheck(app(RepositoryInterface::class));
}

/**
 * @param  array<int, array{check: string, status: string, detail: string}>  $results
 * @return list<string>
 */
function failDetails(array $results): array
{
    return array_values(array_map(
        fn (array $result): string => $result['detail'],
        array_filter($results, fn (array $result): bool => $result['status'] === 'fail')
    ));
}

it('passes when declared dependencies exist and are enabled', function (): void {
    writeFixtureModule('A', requires: ['B']);
    writeFixtureModule('B');

    $result = (dependencyCheck())();

    expect($result)->toHaveCount(1)
        ->and($result[0])->toMatchArray(['status' => 'pass', 'check' => 'module dependencies']);
});

it('fails when a declared dependency is not installed', function (): void {
    writeFixtureModule('A', requires: ['Ghost']);

    $result = (dependencyCheck())();

    expect(failDetails($result))->toContain('Ghost is not an installed module');
});

it('fails when a declared dependency is disabled', function (): void {
    writeFixtureModule('A', requires: ['B']);
    writeFixtureModule('B', enabled: false);

    $result = (dependencyCheck())();

    expect(failDetails($result))->toContain('B is disabled');
});

it('fails on a circular dependency', function (): void {
    writeFixtureModule('A', requires: ['B']);
    writeFixtureModule('B', requires: ['A']);

    $result = (dependencyCheck())();

    expect(failDetails($result))->toContain('A -> B -> A');
});

it('reports multiple failures at once', function (): void {
    writeFixtureModule('A', requires: ['Ghost']);
    writeFixtureModule('B', requires: ['C']);

    $result = (dependencyCheck())();

    expect(failDetails($result))->toContain('Ghost is not an installed module')
        ->toContain('C is not an installed module');
});
