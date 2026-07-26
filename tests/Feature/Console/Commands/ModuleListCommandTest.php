<?php

declare(strict_types=1);

describe('module:list command', function () {
    it('runs successfully', function () {
        $this->artisan('module:list')
            ->assertSuccessful();
    });
});
