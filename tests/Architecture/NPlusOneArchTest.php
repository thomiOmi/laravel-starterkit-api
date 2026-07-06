<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Modules\IAM\Actions\GetAuthenticatedUserAction;
use Modules\IAM\Actions\ListRolesAction;
use Modules\IAM\Actions\ListUsersAction;
use Modules\IAM\Actions\ShowRoleAction;
use Modules\IAM\Actions\ShowUserAction;
use Modules\IAM\Resources\RoleResource;
use Modules\IAM\Resources\UserResource;

arch('shouldBeStrict is enabled in non-production')
    ->expect('App\Providers\AppServiceProvider')
    ->toHaveMethod('configureDefaults');

test('listen and show actions call ->with() to eager-load relationships', function (string $actionClass, string $description) {
    $reflection = new ReflectionClass($actionClass);
    $method = $reflection->getMethod('handle');

    $filename = $method->getFileName();
    $startLine = $method->getStartLine();
    $endLine = $method->getEndLine();

    $source = implode('', array_slice(file($filename), $startLine - 1, $endLine - $startLine + 1));

    $hasWith = str_contains($source, '->with(') || str_contains($source, '::with(');
    expect($hasWith)->toBeTrue();
})->with([
    [ShowUserAction::class, 'ShowUserAction'],
    [ShowRoleAction::class, 'ShowRoleAction'],
    [ListUsersAction::class, 'ListUsersAction'],
    [ListRolesAction::class, 'ListRolesAction'],
]);

test('resources check relationLoaded() before accessing relationships', function (string $resourceClass, string $description) {
    $reflection = new ReflectionClass($resourceClass);
    $method = $reflection->getMethod('toArray');

    $filename = $method->getFileName();
    $startLine = $method->getStartLine();
    $endLine = $method->getEndLine();

    $source = implode('', array_slice(file($filename), $startLine - 1, $endLine - $startLine + 1));

    expect($source)->toContain('relationLoaded(');
})->with([
    [UserResource::class, 'UserResource'],
    [RoleResource::class, 'RoleResource'],
]);

test('GetAuthenticatedUserAction uses loadMissing', function () {
    $reflection = new ReflectionMethod(GetAuthenticatedUserAction::class, 'handle');

    $filename = $reflection->getFileName();
    $startLine = $reflection->getStartLine();
    $endLine = $reflection->getEndLine();

    $source = implode('', array_slice(file($filename), $startLine - 1, $endLine - $startLine + 1));

    expect($source)->toContain('loadMissing(');
});

test('Model::shouldBeStrict is called with correct condition', function () {
    $reflection = new ReflectionClass(AppServiceProvider::class);
    $method = $reflection->getMethod('configureDefaults');

    $filename = $method->getFileName();
    $startLine = $method->getStartLine();
    $endLine = $method->getEndLine();

    $source = implode('', array_slice(file($filename), $startLine - 1, $endLine - $startLine + 1));

    expect($source)->toContain('Model::shouldBeStrict(');
    expect($source)->toContain('! app()->isProduction()');
});
