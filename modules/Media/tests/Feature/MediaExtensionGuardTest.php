<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Contracts\HasMedia;
use Modules\Media\Support\DisallowedExtensions;
use Modules\Media\Support\FileNamer\MediaFileNamer;
use Modules\Media\Traits\InteractsWithMedia;

covers(DisallowedExtensions::class);

describe('Media extension guard', function (): void {
    beforeEach(function (): void {
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

    it('detects disallowed segments in every part of the file name', function (): void {
        expect(DisallowedExtensions::contains('shell.php.jpg'))->toBeTrue()
            ->and(DisallowedExtensions::contains('photo.JPG'))->toBeFalse()
            ->and(DisallowedExtensions::contains('archive.SVG'))->toBeTrue()
            ->and(DisallowedExtensions::contains('.htaccess'))->toBeTrue()
            ->and(DisallowedExtensions::contains('app.jsp'))->toBeTrue()
            ->and(DisallowedExtensions::contains('run.cgi'))->toBeTrue()
            ->and(DisallowedExtensions::contains('noextension'))->toBeFalse()
            ->and(DisallowedExtensions::contains('photo.webp'))->toBeFalse();
    });

    it('falls back to the static default when the config key is missing', function (): void {
        config()->offsetUnset('media.disallowed_extensions');

        expect(DisallowedExtensions::contains('shell.php.jpg'))->toBeTrue()
            ->and(DisallowedExtensions::contains('photo.webp'))->toBeFalse();
    });

    it('rejects double extensions over http with a validation error', function (): void {
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);

        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->image('shell.php.jpg', 32, 32),
        ]);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['file']);
    });

    it('rejects files the target collection does not accept', function (): void {
        config(['media.allowed_extensions' => ['pdf']]);
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);

        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
            'collection_name' => 'avatars',
        ]);

        assertProblemResponse($response, 400);
    });

    it('rejects programmatically attached files the collection refuses', function (): void {
        $user = loginAsUser();

        expect(fn (): mixed => $user->addMedia(UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'))->toMediaCollection('avatars'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('honours a collection acceptsFile callback', function (): void {
        $owner = new #[Table(name: 'users', key: 'id', keyType: 'string')] #[WithoutIncrementing] class extends Model implements HasMedia
        {
            use InteractsWithMedia;

            public function registerMediaCollections(): void
            {
                $this->addMediaCollection('docs')->acceptsFile(fn (): bool => false);
            }
        };

        $owner->forceFill(['id' => (string) Str::ulid()]);
        $owner->exists = true;

        expect(fn (): mixed => $owner->addMedia(UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'))->toMediaCollection('docs'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('honours a collection acceptsExtensions list', function (): void {
        $owner = new #[Table(name: 'users', key: 'id', keyType: 'string')] #[WithoutIncrementing] class extends Model implements HasMedia
        {
            use InteractsWithMedia;

            public function registerMediaCollections(): void
            {
                $this->addMediaCollection('docs')->acceptsExtensions(['PDF']);
            }
        };

        $owner->forceFill(['id' => (string) Str::ulid()]);
        $owner->exists = true;

        $media = $owner->addMedia(UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'))->toMediaCollection('docs');

        expect($media->file_name)->toEndWith('.pdf')
            ->and(fn (): mixed => $owner->addMedia(UploadedFile::fake()->image('photo.jpg', 20, 20))->toMediaCollection('docs'))->toThrow(InvalidArgumentException::class);
    });

    it('rejects a custom namer that produces an executable name and removes the file', function (): void {
        config(['media.file_namer' => EvilPhpFileNamer::class]);
        config(['media.allowed_extensions' => ['pdf']]);
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);

        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ]);

        assertProblemResponse($response, 400);
        Storage::disk('public')->assertMissing('default/evil.php');
    });
});

final class EvilPhpFileNamer implements MediaFileNamer
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
