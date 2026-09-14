<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Actions\UploadMediaAction;
use Modules\Media\Models\Media;
use Modules\Media\Support\FileNamer\MediaFileNamer;
use Modules\Media\Support\Scanners\MediaScanner;
use Modules\Media\Support\StorageOptions;

covers(UploadMediaAction::class);

describe('Media hardening', function () {
    beforeEach(function () {
        Storage::fake('public');
        Storage::fake('local');
        DB::table('permissions')->insertOrIgnore([
            'id' => (string) Str::ulid(),
            'name' => PermissionEnum::MediaCreate->value,
            'guard_name' => 'sanctum',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    describe('content-based mime validation', function () {
        it('rejects executable bytes renamed to an image extension', function () {
            $user = loginAsUser();
            $user->givePermissionTo(PermissionEnum::MediaCreate->value);

            $response = $this->post('/api/v1/media', [
                'file' => UploadedFile::fake()->createWithContent('photo.png', '<?php echo "pwned"; ?>'),
                'collection_name' => 'avatars',
            ]);

            assertProblemResponse($response, 422, 'validation');
            $response->assertJsonValidationErrors(['file']);
            expect(Media::query()->count())->toBe(0);
        });

        it('rejects plain text bytes with an image extension', function () {
            $user = loginAsUser();
            $user->givePermissionTo(PermissionEnum::MediaCreate->value);

            $response = $this->post('/api/v1/media', [
                'file' => UploadedFile::fake()->createWithContent('photo.jpg', 'just some text, not an image'),
            ]);

            assertProblemResponse($response, 422, 'validation');
            $response->assertJsonValidationErrors(['file']);
            expect(Media::query()->count())->toBe(0);
        });

        it('rejects spoofed content on the programmatic path', function () {
            $user = loginAsUser();

            expect(fn (): mixed => $user->addMedia(UploadedFile::fake()->createWithContent('photo.png', '<?php echo "pwned"; ?>'))->toMediaCollection('avatars'))
                ->toThrow(InvalidArgumentException::class)
                ->and(Media::query()->count())->toBe(0);
        });
    });

    describe('image dimension limits', function () {
        it('rejects images wider than the configured maximum', function () {
            config(['media.image.max_width' => 10]);
            $user = loginAsUser();
            $user->givePermissionTo(PermissionEnum::MediaCreate->value);

            $response = $this->post('/api/v1/media', [
                'file' => UploadedFile::fake()->image('photo.jpg', 64, 48),
                'collection_name' => 'avatars',
            ]);

            assertProblemResponse($response, 422, 'validation');
            $response->assertJsonValidationErrors(['file']);
            expect(Media::query()->count())->toBe(0);
        });

        it('rejects images exceeding the pixel budget', function () {
            config(['media.image.max_pixels' => 100]);
            $user = loginAsUser();

            expect(fn (): mixed => $user->addMedia(UploadedFile::fake()->image('photo.jpg', 64, 48))->toMediaCollection('avatars'))
                ->toThrow(InvalidArgumentException::class, 'dimensions')
                ->and(Media::query()->count())->toBe(0);
        });
    });

    describe('private and public storage alignment', function () {
        it('refuses private media forced onto the public disk', function () {
            $user = loginAsUser();

            expect(fn (): mixed => $user->addMedia(UploadedFile::fake()->createWithContent('notes.txt', 'hello world'))->withDisk('public')->toMediaCollection('default'))
                ->toThrow(RuntimeException::class)
                ->and(Media::query()->count())->toBe(0);
        });

        it('refuses public media forced onto the private disk', function () {
            $user = loginAsUser();

            expect(fn (): mixed => $user->addMedia(UploadedFile::fake()->image('photo.jpg', 20, 20))->withDisk('local')->toMediaCollection('avatars'))
                ->toThrow(RuntimeException::class)
                ->and(Media::query()->count())->toBe(0);
        });

        it('refuses conversions pinned to the wrong visibility disk', function () {
            $user = loginAsUser();

            expect(fn (): mixed => $user->addMedia(UploadedFile::fake()->createWithContent('notes.txt', 'hello world'))->storingConversionsOnDisk('public')->toMediaCollection('default'))
                ->toThrow(RuntimeException::class)
                ->and(Media::query()->count())->toBe(0);
        });

        it('refuses unknown disks', function () {
            $user = loginAsUser();

            expect(fn (): mixed => $user->addMedia(UploadedFile::fake()->createWithContent('notes.txt', 'hello world'))->withDisk('nope')->toMediaCollection('default'))
                ->toThrow(RuntimeException::class)
                ->and(Media::query()->count())->toBe(0);
        });

        it('ignores visibility overrides smuggled through custom headers', function () {
            $user = loginAsUser();

            $media = $user->addMedia(UploadedFile::fake()->createWithContent('notes.txt', 'hello world'))
                ->addCustomHeaders(['visibility' => 'public'])
                ->toMediaCollection('default');

            expect($media->disk)->toBe('local')
                ->and($media->visibility->value)->toBe('private')
                ->and(StorageOptions::forVisibility('private', ['visibility' => 'public'])['visibility'] ?? null)->toBe('private');
        });
    });

    describe('malware scanning hook', function () {
        it('rejects uploads flagged by the configured scanner without storing anything', function () {
            app()->instance(MediaScanner::class, new RejectingMediaScanner);
            $user = loginAsUser();
            $user->givePermissionTo(PermissionEnum::MediaCreate->value);

            $response = $this->post('/api/v1/media', [
                'file' => UploadedFile::fake()->image('photo.jpg', 20, 20),
                'collection_name' => 'avatars',
            ]);

            assertProblemResponse($response, 400);
            expect(Media::query()->count())->toBe(0)
                ->and(Storage::disk('public')->allFiles())->toBeEmpty();
        });
    });

    describe('partial storage failures', function () {
        it('removes the processed file when the namer collides after storing', function () {
            config(['media.file_namer' => TakenNameFileNamer::class]);
            $user = loginAsUser();
            $user->givePermissionTo(PermissionEnum::MediaCreate->value);
            Storage::disk('public')->put('avatars/taken.webp', 'taken');

            $response = $this->post('/api/v1/media', [
                'file' => UploadedFile::fake()->image('photo.jpg', 20, 20),
                'collection_name' => 'avatars',
            ]);

            assertProblemResponse($response, 400);
            expect(Media::query()->count())->toBe(0)
                ->and(Storage::disk('public')->allFiles('avatars'))->toBe(['avatars/taken.webp']);
        });

        it('removes the raw file when the database write fails validation after storing', function () {
            config(['media.file_namer' => HardeningEvilFileNamer::class]);
            config(['media.allowed_extensions' => ['txt']]);
            $user = loginAsUser();
            $user->givePermissionTo(PermissionEnum::MediaCreate->value);

            $response = $this->post('/api/v1/media', [
                'file' => UploadedFile::fake()->createWithContent('notes.txt', 'hello world'),
            ]);

            assertProblemResponse($response, 400);
            expect(Media::query()->count())->toBe(0);
            Storage::disk('local')->assertMissing('default/evil.php');
        });
    });
});

final class RejectingMediaScanner implements MediaScanner
{
    public function scan(string $realPath): void
    {
        throw new InvalidArgumentException(__('validation.media_malware_detected'));
    }
}

final class TakenNameFileNamer implements MediaFileNamer
{
    public function originalFileName(string $fileName): string
    {
        return 'taken.webp';
    }

    public function conversionFileName(string $fileName, string $conversion): string
    {
        return 'taken-'.$conversion.'.webp';
    }

    public function responsiveFileName(string $fileName): string
    {
        return $fileName;
    }
}

final class HardeningEvilFileNamer implements MediaFileNamer
{
    public function originalFileName(string $fileName): string
    {
        return 'evil.php';
    }

    public function conversionFileName(string $fileName, string $conversion): string
    {
        return 'evil-'.$conversion.'.php';
    }

    public function responsiveFileName(string $fileName): string
    {
        return $fileName;
    }
}
