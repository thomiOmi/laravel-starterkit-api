<?php

declare(strict_types=1);

beforeEach(function (): void {
    clearFixtureModules();
});

afterEach(function (): void {
    clearFixtureModules();
});

function bindDependencyCheck(): void
{
    bindFixtureModulePaths();
}

it('reports success when dependencies are valid', function (): void {
    writeFixtureModule('A', requires: ['B']);
    writeFixtureModule('B');
    bindDependencyCheck();

    artisanCommand($this, 'module:check-dependencies')
        ->expectsOutputToContain('All module dependency checks passed.')
        ->assertSuccessful();
});

it('reports failure for a missing dependency', function (): void {
    writeFixtureModule('A', requires: ['Ghost']);
    bindDependencyCheck();

    artisanCommand($this, 'module:check-dependencies')
        ->expectsOutputToContain('Ghost is not an installed module')
        ->assertFailed();
});

it('reports failure for a disabled dependency', function (): void {
    writeFixtureModule('A', requires: ['B']);
    writeFixtureModule('B', enabled: false);
    bindDependencyCheck();

    artisanCommand($this, 'module:check-dependencies')
        ->expectsOutputToContain('B is disabled')
        ->assertFailed();
});

it('reports failure for a circular dependency', function (): void {
    writeFixtureModule('A', requires: ['B']);
    writeFixtureModule('B', requires: ['A']);
    bindDependencyCheck();

    artisanCommand($this, 'module:check-dependencies')
        ->expectsOutputToContain('A -> B -> A')
        ->assertFailed();
});
