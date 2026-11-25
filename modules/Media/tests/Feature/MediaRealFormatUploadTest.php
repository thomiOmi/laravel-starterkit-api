<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Enums\MediaType;
use Modules\Media\Http\Controllers\V1\MediaUploadController;
use Modules\Media\Models\Media;
use Modules\Media\Support\MediaMimeType;

covers(MediaUploadController::class);

/**
 * Build a ZIP in memory from entries. ODF needs the `mimetype` entry first
 * and stored uncompressed so libmagic recognizes the document type.
 *
 * @param  array<string, string>  $entries
 */
function mediaRealFormatZip(array $entries, ?string $mimetype = null): string
{
    $path = (string) tempnam(sys_get_temp_dir(), 'media-format');

    try {
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE);

        if ($mimetype !== null) {
            $zip->addFromString('mimetype', $mimetype);
            $zip->setCompressionName('mimetype', ZipArchive::CM_STORE);
        }

        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();

        return (string) file_get_contents($path);
    } finally {
        File::delete($path);
    }
}

function mediaRealFormatOfficeBytes(string $part): string
{
    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        .'<Default Extension="xml" ContentType="application/xml"/>'
        .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
        .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' // @pest-ignore-profanity
        .'<Override PartName="/ppt/presentation.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml"/>'
        .'</Types>';

    $rels = '<?xml version="1.0" encoding="UTF-8"?>'
        .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="'.$part.'"/>'
        .'</Relationships>';

    return mediaRealFormatZip([
        '[Content_Types].xml' => $contentTypes,
        '_rels/.rels' => $rels,
        $part => '<document/>',
    ]);
}

function mediaRealFormatBytes(string $filename): string
{
    return match ($filename) {
        'document.pdf' => "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF\n",
        'document.docx' => mediaRealFormatOfficeBytes('word/document.xml'),
        'document.xlsx' => mediaRealFormatOfficeBytes('xl/workbook.xml'),
        'document.pptx' => mediaRealFormatOfficeBytes('ppt/presentation.xml'),
        'document.odt' => mediaRealFormatZip([
            'META-INF/manifest.xml' => '<manifest/>',
            'content.xml' => '<office:document-content/>',
        ], 'application/vnd.oasis.opendocument.text'),
        'notes.txt' => "plain text body\n",
        'rows.csv' => "a,b,c\n1,2,3\n",
        'clip.mp4' => "\x00\x00\x00\x18ftypisom\x00\x00\x02\x00isomiso2".str_repeat("\x00", 64),
        'clip.webm' => "\x1A\x45\xDF\xA3\x9F\x42\x86\x81\x01\x42\xF7\x81\x01\x42\xF2\x81\x04webm".str_repeat("\x00", 64),
        'clip.mov' => "\x00\x00\x00\x14ftypqt  \x00\x00\x02\x00qt  ".str_repeat("\x00", 64),
        'tone.mp3' => 'ID3'.chr(4).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).str_repeat("\xFF\xFB\x90\x00", 32),
        'tone.wav' => 'RIFF'.pack('V', 100).'WAVEfmt '.pack('V', 16).pack('v', 1).pack('v', 1).pack('V', 8000).pack('V', 16000).pack('v', 1).pack('v', 8).'data'.pack('V', 64).str_repeat(chr(128), 64),
        'tone.ogg' => "OggS\x00\x02".str_repeat("\x00", 25)."\x01\x1Evorbis".str_repeat("\x00", 64),
        'still.avif' => "\x00\x00\x00\x18ftypavif\x00\x00\x00\x00avifmif1".str_repeat("\x00", 64),
        'still.heic' => "\x00\x00\x00\x18ftypheic\x00\x00\x00\x00heicmif1".str_repeat("\x00", 64),
        default => throw new InvalidArgumentException("Unknown format {$filename}"),
    };
}

describe('real format uploads', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
        ensurePermissionExists(PermissionEnum::MediaCreate->value);
    });

    it('records the observed result for each generated format', function (string $filename, bool $accepted): void {
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);

        $file = UploadedFile::fake()->createWithContent($filename, mediaRealFormatBytes($filename));
        $realPath = $file->getRealPath();
        $detected = is_string($realPath) && $realPath !== '' ? MediaMimeType::detect($realPath) : null;

        $response = $this->post('/api/v1/media', ['file' => $file]);

        if ($accepted) {
            assertSuccessResponse($response, 201);

            $media = Media::query()->sole();

            expect($media->mime_type)->toBe($detected)
                ->and($response->json('data.media.type'))->toBe(MediaType::fromMime((string) $detected)->value);
        } else {
            assertProblemResponse($response, 422, 'validation');
            $response->assertJsonValidationErrors(['file']);
        }
    })->with([
        'pdf' => ['document.pdf', true],
        'docx' => ['document.docx', true],
        'xlsx' => ['document.xlsx', true],
        'pptx' => ['document.pptx', true],
        'odt' => ['document.odt', true],
        'txt' => ['notes.txt', true],
        'csv' => ['rows.csv', true],
        'mp4' => ['clip.mp4', true],
        'mov' => ['clip.mov', true],
        'webm' => ['clip.webm', false],
        'mp3' => ['tone.mp3', true],
        'wav' => ['tone.wav', true],
        'ogg' => ['tone.ogg', false],
        'avif' => ['still.avif', true],
        'heic' => ['still.heic', true],
    ]);
});
