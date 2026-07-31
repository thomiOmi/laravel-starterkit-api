<?php

declare(strict_types=1);

use App\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

covers(ResetPassword::class);

describe('ResetPassword notification', function () {

    it('implements ShouldQueue', function () {
        $reflection = new ReflectionClass(ResetPassword::class);

        expect($reflection->implementsInterface(ShouldQueue::class))->toBeTrue();
    });

    it('uses Queueable trait', function () {
        expect(Queueable::class)->toBeIn(class_uses(ResetPassword::class));
    });

});
