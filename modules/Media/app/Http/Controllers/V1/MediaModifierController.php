<?php

declare(strict_types=1);

namespace Modules\Media\Http\Controllers\V1;

use App\Contracts\Identity;
use App\Http\Controllers\Controller;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Response as HttpResponse;
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
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['modifiers' => $e->getMessage()]);
        }

        /** @var string $format */
        $format = isset($parsed['f']) && is_string($parsed['f']) ? $parsed['f'] : 'webp';

        if ($format === 'jpeg') {
            $format = 'jpg';
        }

        $updatedAt = $media->updated_at;
        $version = $updatedAt !== null ? (string) $updatedAt->timestamp : '0';
        $cacheKey = MediaConversion::toCacheKey($parsed, $version);
        $etag = '"'.hash('xxh128', $version.'|'.$media->id.'|'.$cacheKey.'|'.$format).'"';

        if (request()->headers->get('If-None-Match') === $etag) {
            /** @var HttpResponse $notModified */
            $notModified = response('', Response::HTTP_NOT_MODIFIED, ['ETag' => $etag]);

            return $notModified;
        }

        $conversionPath = $this->conversionAction->buildConversionPath($media, $parsed, $cacheKey, $format);
        $disk = Storage::disk($media->disk);
        $isPublic = $media->isPublic();

        if ($disk->exists($conversionPath)) {
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

        $this->conversionAction->handle($media, $parsed, $conversionPath);

        $response = Image::fromStorage($conversionPath, $media->disk)->toResponse(request())->setEtag($etag);

        if ($isPublic) {
            return $response->setMaxAge(self::MAX_AGE)->setPublic();
        }

        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
