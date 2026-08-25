<?php

declare(strict_types=1);

use Pest\Rector\Set\PestSetList;
use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/tests',
        __DIR__.'/modules/*/tests',
    ])
    ->withPhpSets(php84: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        // typeDeclarations: true,
        // privatization: true,
        // earlyReturn: true,
        // codingStyle: true,
    )
    ->withSets([
        PestSetList::CODING_STYLE,
        PHPUnitSetList::COMPOSER_BASED,
    ]);
