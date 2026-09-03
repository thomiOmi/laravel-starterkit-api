<?php

declare(strict_types=1);

use Modules\Media\Enums\MediaVisibilityEnum;

covers(MediaVisibilityEnum::class);

describe('MediaVisibilityEnum', function () {
    it('has the public visibility', fn () => expect(MediaVisibilityEnum::Public->value)->toBe('public'));
    it('has the private visibility', fn () => expect(MediaVisibilityEnum::Private->value)->toBe('private'));

    describe('label', function () {
        it('returns the English label by default', function () {
            expect(MediaVisibilityEnum::Public->label())->toBe('Public')
                ->and(MediaVisibilityEnum::Private->label())->toBe('Private');
        });

        it('returns the Indonesian label in the id locale', function () {
            app()->setLocale('id');

            expect(MediaVisibilityEnum::Public->label())->toBe('Publik')
                ->and(MediaVisibilityEnum::Private->label())->toBe('Pribadi');
        });
    });
});
