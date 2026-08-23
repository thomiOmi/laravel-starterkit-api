<?php

declare(strict_types=1);

use Modules\IAM\Models\SocialAccount;

covers(SocialAccount::class);

describe('SocialAccount', function () {
    it('has expected fillable attributes', function () {
        expect((new SocialAccount)->getFillable())
            ->toContain('user_id', 'provider', 'provider_id', 'avatar');
    });

    it('belongs to a user', function () {
        $relation = new SocialAccount()->user();

        expect($relation->getForeignKeyName())->toBe('user_id');
    });
});
