<?php

declare(strict_types=1);

namespace Modules\Media\Http\Controllers\V1;

use App\Contracts\Identity;
use App\Http\Controllers\Controller;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Media\Actions\GenerateOnDemandConversionAction;
use Modules\Media\Models\Media;
use Modules\Media\Support\MediaConversion;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class MediaModifierController extends Controller
{
    private const int MAX_AGE = 31536000;

    public function __construct(
        private GenerateOnDemandConversionAction $conversionAction,
    ) {}

    public function __invoke(#[CurrentUser] Identity $currentUser, Media $media, string $modifiers): HttpResponse|StreamedResponse
    {
        Gate::authorize('view', $media);

        if (! str_starts_with($media->mime_type, 'image/')) {
            throw ValidationException::withMessages(['media' => __('validation.media_not_image')]);
        }

        try {
            /** @var array<string, mixed> $parsed */
            $parsed = MediaConversion::parse($modifiers);
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw ValidationException::withMessages(['modifiers' => $invalidArgumentException->getMessage()]);
        }

        $format = isset($parsed['f']) && is_string($parsed['f']) ? $parsed['f'] : 'webp';

        if ($format === 'jpeg') {
            $format = 'jpg';
        }

        $version = $media->sha256 ?? (string) ($media->updated_at?->format('U.u') ?? '0');
        $cacheKey = MediaConversion::toCacheKey($parsed, $version);
        $etag = '"'.hash('xxh128', $version.'|'.$media->id.'|'.$cacheKey.'|'.$format).'"';

        if (request()->headers->get('If-None-Match') === $etag) {
            /** @var HttpResponse $notModified */
            $notModified = response('', Response::HTTP_NOT_MODIFIED, ['ETag' => $etag]);

            return $notModified;
        }

        $conversionPath = MediaConversion::derivedPath((string) $media->id, $parsed, $cacheKey, $format);
        $disk = Storage::disk($media->conversions_disk ?? $media->disk);
        $isPublic = $media->isPublic();

        if ($this->conversionExists($media, $conversionPath)) {
            /** @var StreamedResponse $cached */
            $cached = $disk->response($conversionPath);
            $cached->setEtag($etag);

            if ($isPublic) {
                return $cached->setMaxAge(self::MAX_AGE)->setPublic();
            }

            $cached->setPrivate();
            $cached->headers->set('Cache-Control', 'private, no-store');

            return $cached;
        }

        $lock = Cache::lock('media:derived:'.$media->id.':'.$cacheKey, 60);
        $lock->block(10);

        try {
            if (! $this->conversionExists($media, $conversionPath)) {
                $this->conversionAction->handle($media, $parsed, $conversionPath);
            }
        } finally {
            $lock->release();
        }

        $response = Image::fromStorage($conversionPath, $media->conversions_disk ?? $media->disk)->toResponse(request())->setEtag($etag);

        if ($isPublic) {
            return $response->setMaxAge(self::MAX_AGE)->setPublic();
        }

        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    /** @phpstan-impure */
    private function conversionExists(Media $media, string $path): bool
    {
        return Storage::disk($media->conversions_disk ?? $media->disk)->exists($path);
    }
}
