<?php

declare(strict_types=1);

use Modules\Media\Enums\MediaType;

covers(MediaType::class);

it('maps detected mime types to coarse media types', function (string $mime, MediaType $expected): void {
    expect(MediaType::fromMime($mime))->toBe($expected);
})->with([
    'jpeg' => ['image/jpeg', MediaType::Image],
    'png' => ['image/png', MediaType::Image],
    'webp' => ['image/webp', MediaType::Image],
    'avif' => ['image/avif', MediaType::Image],
    'heic' => ['image/heic', MediaType::Image],
    'mp4' => ['video/mp4', MediaType::Video],
    'quicktime' => ['video/quicktime', MediaType::Video],
    'webm' => ['video/webm', MediaType::Video],
    'mp3' => ['audio/mpeg', MediaType::Audio],
    'wav' => ['audio/x-wav', MediaType::Audio],
    'ogg' => ['audio/ogg', MediaType::Audio],
    'pdf' => ['application/pdf', MediaType::Document],
    'doc' => ['application/msword', MediaType::Document],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', MediaType::Document],
    'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', MediaType::Document], // @pest-ignore-profanity
    'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', MediaType::Document],
    'odt' => ['application/vnd.oasis.opendocument.text', MediaType::Document],
    'rtf' => ['application/rtf', MediaType::Document],
    'plain text' => ['text/plain', MediaType::Document],
    'csv' => ['text/csv', MediaType::Document],
    'archive' => ['application/zip', MediaType::Other],
    'opaque bytes' => ['application/octet-stream', MediaType::Other],
    'empty' => ['', MediaType::Other],
    'uppercase with charset' => ['Text/Plain; charset=utf-8', MediaType::Document],
    'uppercase image' => ['IMAGE/JPEG', MediaType::Image],
]);
