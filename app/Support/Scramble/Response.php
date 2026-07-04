<?php

declare(strict_types=1);

namespace App\Support\Scramble;

use Dedoc\Scramble\Support\Generator\Response as BaseResponse;

class Response extends BaseResponse
{
    /** @var array<string, array<string, mixed>> */
    private array $mediaTypeExamples = [];

    /**
     * @param  array<string, mixed>  $example
     * @return $this
     */
    public function setMediaTypeExample(string $mediaType, array $example): static
    {
        $this->mediaTypeExamples[$mediaType] = $example;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'description' => $this->description,
        ];

        $content = [];
        foreach ($this->content as $mediaType => $schema) {
            $entry = ['schema' => $schema->toArray()];
            if (isset($this->mediaTypeExamples[$mediaType])) {
                $entry['example'] = $this->mediaTypeExamples[$mediaType];
            }
            $content[$mediaType] = $entry;
        }
        if ($content !== []) {
            $result['content'] = $content;
        }

        $headers = [];
        foreach ($this->headers as $name => $header) {
            $headers[$name] = $header->toArray();
        }

        $links = [];
        foreach ($this->links as $name => $link) {
            $links[$name] = $link->toArray();
        }

        return array_merge(
            $result,
            $headers !== [] ? ['headers' => $headers] : [],
            $links !== [] ? ['links' => $links] : [],
            $this->extensionPropertiesToArray(),
        );
    }
}
