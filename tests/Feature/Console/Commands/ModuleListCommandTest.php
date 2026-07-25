<?php

declare(strict_types=1);

test('runs successfully', function () {
    $this->artisan('module:list')
        ->assertSuccessful();
});
