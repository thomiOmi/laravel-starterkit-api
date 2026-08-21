<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\FormRequest;
use Modules\IAM\Http\Requests\V1\LoginRequest;

covers(LoginRequest::class);

describe('LoginRequest', function () {
    it('extends FormRequest and authorizes all', function () {
        $parent = new ReflectionClass(LoginRequest::class)->getParentClass();

        expect($parent)->toBeInstanceOf(ReflectionClass::class);

        if ($parent instanceof ReflectionClass) {
            expect($parent->getName())->toBe(FormRequest::class)
                ->and((new LoginRequest)->authorize())->toBeTrue();
        }
    });

    it('defines expected validation rules', function () {
        $request = new LoginRequest;
        $rules = $request->rules();

        expect($rules)->toHaveKeys(['email', 'password', 'device_name'])
            ->and($rules['device_name'])->toContain('nullable', 'string', 'max:255');
    });

    it('exposes payload via FormRequest', function () {
        expect(new ReflectionClass(LoginRequest::class)->hasMethod('payload'))->toBeTrue();
    });
});
