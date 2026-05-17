<?php

declare(strict_types=1);

namespace Modules\{Module}\Payloads\V1;

/**
 * Class {Action}{Resource}Payload
 *
 * @package Modules\{Module}\Payloads\V1
 */
final readonly class {Action}{Resource}Payload
{
    /**
     * {Action}{Resource}Payload constructor.
     *
     * @param array $data
     */
    public function __construct(
        public array $data,
    ) {}

    /**
     * Create a new payload from an array.
     *
     * @param array $data
     * @return self
     */
    public static function from(array $data): self
    {
        return new self($data);
    }

    /**
     * Convert the payload to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
