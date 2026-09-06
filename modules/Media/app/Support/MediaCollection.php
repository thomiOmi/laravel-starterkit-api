<?php

declare(strict_types=1);

namespace Modules\Media\Support;

use InvalidArgumentException;

/**
 * Builder for a single media collection definition.
 * Mimics Spatie's MediaCollection API but lightweight.
 */
final class MediaCollection
{
    public string $name;

    public bool $singleFile = false;

    public ?int $collectionSizeLimit = null;

    /** @var array<int, string> */
    public array $acceptsMimeTypes = [];

    /** @var array<int, string> */
    public array $acceptsExtensions = [];

    /** @var callable|null */
    public $acceptsFile = null;

    public ?string $fallbackUrl = null;

    public ?string $fallbackPath = null;

    public ?string $visibility = null;

    public bool $generateResponsiveImages = false;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function singleFile(): self
    {
        $this->singleFile = true;

        return $this;
    }

    public function onlyKeepLatest(int $limit): self
    {
        if ($limit < 1) {
            throw new InvalidArgumentException('Collection size limit must be at least 1.');
        }

        $this->collectionSizeLimit = $limit;

        return $this;
    }

    public function visibility(string $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * @param  array<int, string>  $mimeTypes
     */
    public function acceptsMimeTypes(array $mimeTypes): self
    {
        $this->acceptsMimeTypes = $mimeTypes;

        return $this;
    }

    /**
     * @param  array<int, mixed>  $extensions
     */
    public function acceptsExtensions(array $extensions): self
    {
        $normalized = [];

        foreach ($extensions as $extension) {
            if (is_string($extension) && $extension !== '') {
                $normalized[] = ltrim(strtolower($extension), '.');
            }
        }

        $this->acceptsExtensions = array_values(array_unique($normalized));

        return $this;
    }

    public function acceptsFile(callable $callback): self
    {
        $this->acceptsFile = $callback;

        return $this;
    }

    public function withResponsiveImages(bool $generate = true): self
    {
        $this->generateResponsiveImages = $generate;

        return $this;
    }

    public function useFallbackUrl(string $url): self
    {
        $this->fallbackUrl = $url;

        return $this;
    }

    public function useFallbackPath(string $path): self
    {
        $this->fallbackPath = $path;

        return $this;
    }
}
