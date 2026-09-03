<?php

declare(strict_types=1);

namespace Modules\Media\Support;

/**
 * Builder for a single media conversion definition.
 */
final class MediaConversion
{
    public string $name;

    public ?int $width = null;

    public ?int $height = null;

    public string $fit = 'contain';

    public string $format = 'webp';

    public int $quality = 80;

    /** @var array<int, string> */
    public array $performOnCollections = [];

    public bool $nonQueued = false;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function width(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function height(int $height): self
    {
        $this->height = $height;

        return $this;
    }

    public function fit(string $fit): self
    {
        $this->fit = $fit;

        return $this;
    }

    public function format(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function quality(int $quality): self
    {
        $this->quality = $quality;

        return $this;
    }

    /**
     * @param  array<int, string>|string  $collections
     */
    public function performOnCollections(array|string $collections): self
    {
        $this->performOnCollections = is_array($collections) ? $collections : [$collections];

        return $this;
    }

    public function nonQueued(): self
    {
        $this->nonQueued = true;

        return $this;
    }
}
