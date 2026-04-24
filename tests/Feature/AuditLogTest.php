<?php

declare(strict_types=1);

namespace Tests\Feature;

use Modules\User\Models\User;
use Spatie\Activitylog\Models\Activity;

test('it logs user creation activity', function () {
    $user = User::factory()->create([
        'name' => 'Test User',
    ]);

    $activity = Activity::all()->last();

    expect($activity->description)->toBe('created')
        ->and($activity->subject_id)->toBe($user->id)
        ->and($activity->log_name)->toBe('users');
});

test('it logs user update activity', function () {
    $user = User::factory()->create(['name' => 'Old Name']);

    $user->name = 'Updated Name';
    $user->save();

    $activity = Activity::where('description', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->description)->toBe('updated')
        ->and($activity->log_name)->toBe('users');
});

test('it logs user deletion activity', function () {
    $user = User::factory()->create();

    $user->delete();

    $activity = Activity::all()->last();

    expect($activity->description)->toBe('deleted')
        ->and($activity->subject_id)->toBe($user->id)
        ->and($activity->log_name)->toBe('users');
});
