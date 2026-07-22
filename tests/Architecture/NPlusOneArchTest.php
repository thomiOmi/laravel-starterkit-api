<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Modules\IAM\Actions\ListRolesAction;
use Modules\IAM\Actions\ListUsersAction;
use Modules\IAM\Resources\RoleResource;
use Modules\IAM\Resources\UserResource;

arch('shouldBeStrict is enabled in non-production')
    ->expect('App\Providers\AppServiceProvider')
    ->toHaveMethod('configureDefaults');

test('list actions call ->with() to eager-load relationships', function (string $actionClass, string $description) {
    $reflection = new ReflectionClass($actionClass);
    $method = $reflection->getMethod('handle');

    $filename = $method->getFileName();
    $startLine = $method->getStartLine();
    $endLine = $method->getEndLine();

    $source = implode('', array_slice(file($filename), $startLine - 1, $endLine - $startLine + 1));

    $hasEagerLoad = str_contains($source, '->with(') || str_contains($source, '::with(') || str_contains($source, 'loadMissing(');
    expect($hasEagerLoad)->toBeTrue();
})->with([
    [ListUsersAction::class, 'ListUsersAction'],
    [ListRolesAction::class, 'ListRolesAction'],
]);

test('resources check relationLoaded() or whenLoaded() before accessing relationships', function (string $resourceClass, string $description) {
    $source = file_get_contents((new ReflectionClass($resourceClass))->getFileName());

    expect($source)
        ->toContain('relationLoaded(');
})->with([
    [UserResource::class, 'UserResource'],
    [RoleResource::class, 'RoleResource'],
]);

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
