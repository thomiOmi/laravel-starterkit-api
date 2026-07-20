<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use Modules\IAM\Models\User;

describe('HasDefaultBehavior', function () {
    it('serializes dates in Y-m-d H:i:s format', function () {
        $user = User::factory()->create();

        $userArray = $user->toArray();

        expect($userArray['created_at'])->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/');
    });

    it('uses ULID as primary key', function () {
        $user = User::factory()->create();

        expect($user->id)->toBeString();
        expect(strlen($user->id))->toBe(26);
    });
});
