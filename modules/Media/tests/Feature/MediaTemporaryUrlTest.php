<?php

declare(strict_types=1);

use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Models\Media;

covers(Media::class);

describe('Media::getTemporaryUrl()', function (): void {
    it('signs the original file route when given only an expiration', function (): void {
        // A disk without serving support forces the fallback to a Laravel signed route.
        config(['filesystems.disks.unsigned' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => false,
        ]]);

        $media = MediaFactory::new()->createOne(['disk' => 'unsigned']);
        $expiration = now()->addMinutes(15);

        $url = $media->getTemporaryUrl($expiration);

        expect($url)->toContain('/api/v1/media/'.$media->id.'/file')
            ->and($url)->toContain('expires='.$expiration->timestamp)
            ->and($url)->toContain('signature=')
            ->and($url)->not->toContain('conversion');
    });

    it('accepts only the expiration parameter', function (): void {
        $method = new ReflectionMethod(Media::class, 'getTemporaryUrl');

        expect($method->getNumberOfParameters())->toBe(1)
            ->and($method->getParameters()[0]->getName())->toBe('expiration');
    });
});
