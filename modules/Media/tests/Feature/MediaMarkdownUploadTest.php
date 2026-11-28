<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Http\Controllers\V1\MediaUploadController;

covers(MediaUploadController::class);

describe('markdown uploads', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
        ensurePermissionExists(PermissionEnum::MediaCreate->value);
    });

    it('accepts plain markdown as a document', function (): void {
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);

        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->createWithContent('notes.md', "# Title\n\nhello\n"),
            'collection_name' => 'default',
        ]);

        assertSuccessResponse($response, 201);
        expect($response->json('data.media.type'))->toBe('document')
            ->and($response->json('data.media.mime_type'))->toBe('text/plain');
    });

    it('still rejects markdown content carrying script markers', function (): void {
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);

        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->createWithContent('notes.md', "<?php echo 1; ?>\n<script>alert(1)</script>\n"),
            'collection_name' => 'default',
        ]);

        assertProblemResponse($response, 422);
    });
});
