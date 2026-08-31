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

final readonly class MediaVariantController extends Controller
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
        /** @var int<1, 100> $quality */
        $quality = isset($parsed['q']) && is_int($parsed['q']) ? $parsed['q'] : 80;

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
        $variantPath = 'variants/'.$media->id.'/'.$cacheKey.'.'.$ext;

        if ($disk->exists($variantPath)) {
            /** @var StreamedResponse $cached */
            $cached = $disk->response($variantPath);

            return $cached->setEtag($etag)->setMaxAge(self::MAX_AGE)->setPublic();
        }

        $image = Image::fromStorage($media->path, $media->disk);

        if ($width !== null || $height !== null) {
            // Use cover if both dimensions provided and s modifier, otherwise scale
            if (isset($parsed['s']) && $width !== null && $height !== null) {
                $image = $image->cover(width: $width, height: $height);
            } else {
                $image = $image->scale(width: $width, height: $height);
            }
        }

        $image = $image->toFormat($format)->quality($quality);
        $image->storeAs(dirname($variantPath), basename($variantPath), $media->disk, ['visibility' => $media->visibility->value]);

        return $image->toResponse(request())
            ->setEtag($etag)
            ->setMaxAge(self::MAX_AGE)
            ->setPublic();
    }
}
