<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Policies\MediaPolicy;

covers(MediaPolicy::class);

describe('MediaPolicy uploader and visibility branches', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
    });

    it('lets the model owner manage media that belongs to no one else', function (string $ability): void {
        $owner = loginAsUser();

        $media = MediaFactory::new()->forModel($owner)->createOne();

        expect($media->model_id)->toBe($owner->getKey())
            ->and(Gate::forUser($owner)->allows($ability, $media))->toBeTrue();
    })->with(['view', 'delete', 'update']);

    it('denies the owner of a different model', function (string $ability): void {
        $owner = loginAsUser();
        $media = MediaFactory::new()->forModel($owner)->createOne();
        $otherOwner = loginAsUser();

        expect(Gate::forUser($otherOwner)->allows($ability, $media))->toBeFalse();
    })->with(['view', 'delete', 'update']);

    it('lets the uploader manage media that belongs to no model', function (string $ability): void {
        $uploader = loginAsUser();

        $media = MediaFactory::new()->uploadedBy($uploader)->createOne();

        // The uploader is deliberately not the model owner, and holds no media.* permission.
        expect($media->model_id)->toBeNull()
            ->and(Gate::forUser($uploader)->allows($ability, $media))->toBeTrue();
    })->with(['view', 'delete', 'update']);

    it('denies a stranger without media permissions', function (string $ability): void {
        $uploader = loginAsUser();
        $media = MediaFactory::new()->uploadedBy($uploader)->createOne();
        $stranger = loginAsUser();

        expect(Gate::forUser($stranger)->allows($ability, $media))->toBeFalse();
    })->with(['view', 'delete', 'update']);

    it('lets any authenticated user view public media but not delete it', function (): void {
        $uploader = loginAsUser();
        $media = MediaFactory::new()->public()->uploadedBy($uploader)->createOne();
        $stranger = loginAsUser();

        expect(Gate::forUser($stranger)->allows('view', $media))->toBeTrue()
            ->and(Gate::forUser($stranger)->allows('delete', $media))->toBeFalse();
    });

    it('allows the uploader to delete unowned media over http', function (): void {
        $uploader = loginAsUser();
        $media = MediaFactory::new()->uploadedBy($uploader)->createOne();
        Storage::disk($media->disk)->put($media->getPath() ?? '', 'content');

        assertSuccessResponse($this->deleteJson("/api/v1/media/{$media->id}"), 200);

        Storage::disk($media->disk)->assertMissing($media->getPath() ?? '');
    });

    it('lets a super admin manage media owned by someone else', function (): void {
        DB::table('roles')->insertOrIgnore([
            'id' => (string) Str::ulid(),
            'name' => RoleEnum::SuperAdmin->value,
            'guard_name' => 'sanctum',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $owner = loginAsUser();
        $media = MediaFactory::new()->forModel($owner)->createOne();

        $superAdmin = loginAsSuperAdmin();

        expect(Gate::forUser($superAdmin)->allows('delete', $media))->toBeTrue()
            ->and(Gate::forUser($superAdmin)->allows('update', $media))->toBeTrue()
            ->and(Gate::forUser($superAdmin)->allows('view', $media))->toBeTrue();
    });
});
