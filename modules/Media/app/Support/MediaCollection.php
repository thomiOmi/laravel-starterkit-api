<?php

declare(strict_types=1);

namespace Modules\Media\Support;

/**
 * Builder for a single media collection definition.
 * Mimics Spatie's MediaCollection API but lightweight.
 */
final class MediaCollection
{
    public string $name;

    public bool $singleFile = false;

    /** @var array<int, string> */
    public array $acceptsMimeTypes = [];

    /** @var callable|null */
    public $acceptsFile = null;

    public ?string $fallbackUrl = null;

    public ?string $fallbackPath = null;

    public ?string $visibility = null;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function singleFile(): self
    {
        $this->singleFile = true;

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

    public function acceptsFile(callable $callback): self
    {
        $this->acceptsFile = $callback;

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
