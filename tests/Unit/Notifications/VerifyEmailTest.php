<?php

declare(strict_types=1);

use App\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

covers(VerifyEmail::class);

describe('VerifyEmail notification', function () {

    it('implements ShouldQueue', function () {
        $reflection = new ReflectionClass(VerifyEmail::class);

        expect($reflection->implementsInterface(ShouldQueue::class))->toBeTrue();
    });

    it('uses Queueable trait', function () {
        expect(in_array(Queueable::class, class_uses(VerifyEmail::class), true))->toBeTrue();
    });

});
