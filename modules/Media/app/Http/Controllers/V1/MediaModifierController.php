<?php

declare(strict_types=1);

namespace Modules\Media\Http\Controllers\V1;

use App\Contracts\Identity;
use App\Http\Controllers\Controller;
use App\Http\Responses\ProblemResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\Media\Models\Media;
use Modules\Media\Support\MediaModifier;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class MediaModifierController extends Controller
{
    private const int MAX_AGE = 31536000;

    public function __invoke(#[CurrentUser] Identity $currentUser, Media $media, string $modifiers): HttpResponse|StreamedResponse|ProblemResponse
    {
        Gate::authorize('view', $media);

        if (! str_starts_with($media->mime_type, 'image/')) {
            return new ProblemResponse(
                typeKey: 'validation',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                detail: __('validation.media_not_image'),
            );
        }

        try {
            /** @var array<string, mixed> $parsed */
            $parsed = MediaModifier::parse($modifiers);
        } catch (InvalidArgumentException $e) {
            return new ProblemResponse(
                typeKey: 'validation',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                detail: $e->getMessage(),
            );
        }

        /** @var int<1, 2000>|null $width */
        $width = isset($parsed['w']) && is_int($parsed['w']) ? $parsed['w'] : null;
        /** @var int<1, 2000>|null $height */
        $height = isset($parsed['h']) && is_int($parsed['h']) ? $parsed['h'] : null;
        /** @var string $format */
        $format = isset($parsed['f']) && is_string($parsed['f']) ? $parsed['f'] : 'webp';
        /** @var string|null $fit */
        $fit = isset($parsed['fit']) && is_string($parsed['fit']) ? $parsed['fit'] : null;
        /** @var string|null $kernel */
        $kernel = isset($parsed['kernel']) && is_string($parsed['kernel']) ? $parsed['kernel'] : null;
        $defaultQuality = (int) config()->integer("media.collections.{$media->collection_name}.quality", 80);
        if ($defaultQuality < 1 || $defaultQuality > 100) {
            $defaultQuality = 80;
        }
        /** @var int<1, 100> $quality */
        $quality = isset($parsed['q']) && is_int($parsed['q']) ? $parsed['q'] : $defaultQuality;

        if ($format === 'jpeg') {
            $format = 'jpg';
        }

        $updatedAt = $media->updated_at;
        $version = $updatedAt !== null ? (string) $updatedAt->timestamp : '0';
        $cacheKey = MediaModifier::toCacheKey($parsed, $version);
        $etag = '"'.hash('xxh128', $version.'|'.$media->id.'|'.$cacheKey.'|'.$format).'"';

        if (request()->headers->get('If-None-Match') === $etag) {
            /** @var HttpResponse $notModified */
            $notModified = response('', Response::HTTP_NOT_MODIFIED, ['ETag' => $etag]);

            return $notModified;
        }

        $disk = Storage::disk($media->disk);
        $ext = $format === 'jpg' ? 'jpg' : $format;
        $readableParts = [];
        if ($width !== null) {
            $readableParts[] = "w{$width}";
        }
        if ($height !== null) {
            $readableParts[] = "h{$height}";
        }
        if ($format !== '') {
            $readableParts[] = "f_{$format}";
        }
        if ($quality !== 80) {
            $readableParts[] = "q{$quality}";
        }
        if ($fit !== null) {
            $readableParts[] = "fit_{$fit}";
        }
        if ($kernel !== null) {
            $readableParts[] = "kernel_{$kernel}";
        }
        $readable = implode('-', $readableParts);
        if ($readable === '') {
            $readable = 'original';
        }
        $variantPath = 'variants/'.$media->id.'/'.$readable.'-'.substr($cacheKey, 0, 8).'.'.$ext;

        $isPublic = $media->isPublic();

        if ($disk->exists($variantPath)) {
            /** @var StreamedResponse $cached */
            $cached = $disk->response($variantPath);
            $cached->setEtag($etag);

            if ($isPublic) {
                return $cached->setMaxAge(self::MAX_AGE)->setPublic();
            }

            $cached->setPrivate();
            $cached->headers->set('Cache-Control', 'private, no-store');

            return $cached;
        }

        $path = $media->getPath();

        if (! is_string($path)) {
            abort(404);
        }

        $image = Image::fromStorage($path, $media->disk);

        if ($width !== null || $height !== null) {
            if ($fit === 'cover' && $width !== null && $height !== null) {
                $image = $image->cover(width: $width, height: $height);
            } elseif ($fit === 'contain' && $width !== null && $height !== null) {
                $image = $image->contain(width: $width, height: $height);
            } elseif ($fit === 'fill' && $width !== null && $height !== null) {
                $image = $image->resize(width: $width, height: $height);
            } elseif (isset($parsed['s']) && $width !== null && $height !== null) {
                $image = $image->cover(width: $width, height: $height);
            } else {
                $image = $image->scale(width: $width, height: $height);
            }
        }

        // Kernel handling is driver-specific and not directly exposed via Image facade; reserved for custom driver via MediaProcessor
        $image = $image->toFormat($format)->quality($quality);
        $image->storeAs(dirname($variantPath), basename($variantPath), $media->disk, ['visibility' => $media->visibility->value]);

        $response = $image->toResponse(request())->setEtag($etag);

        if ($isPublic) {
            return $response->setMaxAge(self::MAX_AGE)->setPublic();
        }

        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
