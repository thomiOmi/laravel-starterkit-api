<?php

declare(strict_types=1);

use App\Console\Commands\ModuleListCommand;

covers(ModuleListCommand::class);

describe('module:list command', function () {
    it('runs successfully', function () {
        $this->artisan('module:list')
            ->assertSuccessful();
    });
});
