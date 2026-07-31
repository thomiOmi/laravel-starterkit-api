<?php

declare(strict_types=1);

use App\Console\Commands\ModuleListCommand;

covers(ModuleListCommand::class);

describe('module:list command', function () {
    it('runs successfully', function () {
        artisanCommand($this, 'module:list')
            ->assertSuccessful();
    });
});
