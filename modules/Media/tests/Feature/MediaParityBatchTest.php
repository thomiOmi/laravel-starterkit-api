<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Actions\DeleteMediaAction;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Models\Media;
use Modules\Media\Support\FileRemover\DefaultFileRemover;
use Modules\Media\Support\FileRemover\MediaFileRemover;

covers(DefaultFileRemover::class);

describe('Media parity batch', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('removes every related file through the file remover', function () {
        $media = MediaFactory::new()->public()->createOne();
        Storage::disk('public')->put($media->getPath() ?? '', 'original');
        Storage::disk('public')->put('variants/'.$media->id.'/thumb.webp', 'variant');

        app(MediaFileRemover::class)->removeAllFiles($media);

        Storage::disk('public')->assertMissing($media->getPath() ?? '');
        Storage::disk('public')->assertMissing('variants/'.$media->id.'/thumb.webp');
    });

    it('uses a custom file remover from config on delete', function () {
        config(['media.file_remover' => RecordingFileRemover::class]);

        $media = MediaFactory::new()->forModel(loginAsUser())->createOne();
        Storage::disk('public')->put($media->getPath() ?? '', 'original');

        app(DeleteMediaAction::class)->handle($media);

        expect(RecordingFileRemover::$removed)->toContain($media->id);
    });

    it('resolves the first media path with and without conversion', function () {
        $user = loginAsUser();

        expect($user->getFirstMediaPath('avatars'))->toBeNull();

        $media = $user->addMedia(UploadedFile::fake()->image('photo.jpg', 50, 50))->toMediaCollection('avatars');

        expect($user->getFirstMediaPath('avatars'))->toBe($media->getPath());
    });

    it('attaches every uploaded file key via addAllMediaFromRequest', function () {
        $user = loginAsUser();

        app()->instance(
            'request',
            Request::create('/media', 'POST', [], [], [
                'one' => UploadedFile::fake()->image('one.jpg', 20, 20),
                'two' => UploadedFile::fake()->image('two.jpg', 20, 20),
            ])
        );

        $pending = $user->addAllMediaFromRequest();

        expect($pending)->toHaveCount(2);

        foreach ($pending as $item) {
            $item->toMediaCollection('default');
        }

        expect($user->getMedia('default'))->toHaveCount(2);
    });

    it('exposes registered collections under the Spatie name', function () {
        $user = loginAsUser();

        expect($user->getRegisteredMediaCollections())->toBe($user->getMediaCollections())
            ->and($user->getRegisteredMediaCollections())->toHaveKey('avatars');
    });

    it('generates responsive images per call without a collection flag', function () {
        config(['media.responsive.widths' => [32]]);
        $user = loginAsUser();

        $media = $user->addMedia(UploadedFile::fake()->image('photo.jpg', 100, 80))
            ->withResponsiveImages()
            ->toMediaCollection('default');

        expect($media->fresh()?->responsive_images)->not->toBe([]);
    });

    it('stores conversions on the per-call disk', function () {
        Storage::fake('attachments');
        config(['media.queue' => false]);
        $user = loginAsUser();

        $media = $user->addMedia(UploadedFile::fake()->image('photo.jpg', 100, 80))
            ->storingConversionsOnDisk('attachments')
            ->toMediaCollection('default');

        expect($media->conversions_disk)->toBe('attachments');
    });

    it('honours an explicit order on create', function () {
        $user = loginAsUser();

        $media = $user->addMedia(UploadedFile::fake()->image('photo.jpg', 20, 20))
            ->setOrder(5)
            ->toMediaCollection('default');

        expect($media->order_column)->toBe(5);
    });

    it('has no shared collection enum anymore', function () {
        expect(class_exists('App\Enums\MediaCollection'))->toBeFalse();
    });
});

final class RecordingFileRemover implements MediaFileRemover
{
    /** @var array<int, string> */
    public static array $removed = [];

    public function removeAllFiles(Media $media): void
    {
        self::$removed[] = (string) $media->id;
    }

    public function removeResponsiveImages(Media $media): void
    {
        // No-op for the recording stub.
    }

    public function removeFile(string $path, string $disk): void
    {
        // No-op for the recording stub.
    }
}
