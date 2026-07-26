<?php

declare(strict_types=1);

use App\Notifications\VerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;

test('implements ShouldQueue', function () {
    $reflection = new ReflectionClass(VerifyEmail::class);

    expect($reflection->implementsInterface(ShouldQueue::class))->toBeTrue();
    expect(in_array('Illuminate\Bus\Queueable', array_keys($reflection->getTraits())))->toBeTrue();
});
