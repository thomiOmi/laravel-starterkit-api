<?php

declare(strict_types=1);

use App\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

describe('ResetPassword notification', function () {

    it('implements ShouldQueue', function () {
        $reflection = new ReflectionClass(ResetPassword::class);

        expect($reflection->implementsInterface(ShouldQueue::class))->toBeTrue();
    });

    it('uses Queueable trait', function () {
        expect(in_array(Queueable::class, class_uses(ResetPassword::class), true))->toBeTrue();
    });

});
