<?php

declare(strict_types=1);

use Modules\IAM\Models\SocialAccount;

covers(SocialAccount::class);

describe('SocialAccount', function (): void {
    it('has expected fillable attributes', function (): void {
        expect((new SocialAccount)->getFillable())
            ->toContain('user_id', 'provider', 'provider_id', 'avatar');
    });

    it('belongs to a user', function (): void {
        $relation = new SocialAccount()->user();

        expect($relation->getForeignKeyName())->toBe('user_id');
    });
});
