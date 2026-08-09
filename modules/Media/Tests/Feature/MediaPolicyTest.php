<?php

declare(strict_types=1);

use Modules\IAM\Database\Seeders\IAMSeeder;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Database\Seeders\MediaSeeder;
use Modules\Media\Models\Media;

describe('MediaPolicy', function (): void {
    beforeEach(function (): void {
        $this->seed([IAMSeeder::class, MediaSeeder::class]);
    });

    describe('view', function (): void {
        it('allows a user with the view permission', function (): void {
            $user = loginAsUserRole();
            $media = MediaFactory::new()->createOne();

            expect($user->can('view', $media))->toBeTrue();
        })->group('module:media');

        it('denies a user without the view permission', function (): void {
            $user = loginAsUser();
            $media = MediaFactory::new()->createOne();

            expect($user->can('view', $media))->toBeFalse();
        })->group('module:media');

        it('allows a super admin through the gate bypass', function (): void {
            $user = loginAsSuperAdmin();
            $media = MediaFactory::new()->createOne();

            expect($user->can('view', $media))->toBeTrue();
        })->group('module:media');
    });

    describe('create', function (): void {
        it('allows a user with the create permission', function (): void {
            $user = loginAsUserRole();

            expect($user->can('create', Media::class))->toBeTrue();
        })->group('module:media');

        it('denies a user without the create permission', function (): void {
            $user = loginAsUser();

            expect($user->can('create', Media::class))->toBeFalse();
        })->group('module:media');

        it('allows a super admin through the gate bypass', function (): void {
            $user = loginAsSuperAdmin();

            expect($user->can('create', Media::class))->toBeTrue();
        })->group('module:media');
    });

    describe('delete', function (): void {
        it('allows a user to delete their own media', function (): void {
            $user = loginAsUserRole();
            $media = MediaFactory::new()->createOne(['uploaded_by' => $user->id]);

            expect($user->can('delete', $media))->toBeTrue();
        })->group('module:media');

        it('allows a user with the delete permission to delete any media', function (): void {
            $user = loginAsAdmin();
            $media = MediaFactory::new()->createOne();

            expect($user->can('delete', $media))->toBeTrue();
        })->group('module:media');

        it('denies a user without the delete permission deleting others media', function (): void {
            $user = loginAsUserRole();
            $media = MediaFactory::new()->createOne();

            expect($user->can('delete', $media))->toBeFalse();
        })->group('module:media');

        it('allows a super admin through the gate bypass', function (): void {
            $user = loginAsSuperAdmin();
            $media = MediaFactory::new()->createOne();

            expect($user->can('delete', $media))->toBeTrue();
        })->group('module:media');
    });

    describe('route enforcement', function (): void {
        it('returns 403 when listing media without the view permission', function (): void {
            loginAsUser();

            $this->getJson('/api/v1/media')
                ->assertForbidden();
        })->group('module:media');

        it('returns 403 when deleting media owned by someone else without the delete permission', function (): void {
            loginAsUserRole();
            $media = MediaFactory::new()->createOne();

            $this->deleteJson("/api/v1/media/{$media->id}")
                ->assertForbidden();
        })->group('module:media');
    });
});
