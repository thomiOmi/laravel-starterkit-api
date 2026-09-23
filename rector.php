<?php

declare(strict_types=1);

use Pest\Rector\Set\PestSetList;
use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use RectorLaravel\Rector\Class_\AddHasFactoryToModelsRector;
use RectorLaravel\Rector\StaticCall\CarbonToDateFacadeRector;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap/app.php',
        __DIR__.'/routes',
        __DIR__.'/config',
        __DIR__.'/tests',
        __DIR__.'/database',
        __DIR__.'/lang',
        __DIR__.'/modules/*/app',
        __DIR__.'/modules/*/routes',
        __DIR__.'/modules/*/config',
        __DIR__.'/modules/*/tests',
        __DIR__.'/modules/*/database',
        __DIR__.'/modules/*/lang',
    ])
    ->withPhpSets(php84: true)
    ->withComposerBased(laravel: true, phpunit: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
        codingStyle: true,
    )
    ->withSets([
        PestSetList::CODING_STYLE,
        LaravelSetList::LARAVEL_ARRAYACCESS_TO_METHOD_CALL,
        LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
        LaravelSetList::LARAVEL_CONTAINER_STRING_TO_FULLY_QUALIFIED_NAME,
        LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
        LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
        LaravelSetList::LARAVEL_FACTORIES,
        LaravelSetList::LARAVEL_IF_HELPERS,
        LaravelSetList::LARAVEL_LEGACY_FACTORIES_TO_CLASSES,
    ])
    ->withSkip([
        AddArrowFunctionReturnTypeRector::class,
        AddHasFactoryToModelsRector::class,
        CarbonToDateFacadeRector::class,
        __DIR__.'/tests/Architecture/ArchitectureTest.php',
        __DIR__.'/config/database.php',
    ]);
