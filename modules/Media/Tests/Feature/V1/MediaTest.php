<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\IAM\Database\Seeders\IAMSeeder;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Database\Seeders\MediaSeeder;
use Modules\Media\Models\Media;

describe('Media endpoints', function (): void {
    beforeEach(function (): void {
        $this->seed([IAMSeeder::class, MediaSeeder::class]);
        Storage::fake('public');
        Storage::fake('local');
    });

    describe('index', function (): void {
        it('returns a paginated list of media for a user with the view permission', function (): void {
            loginAsUserRole();

            $this->getJson('/api/v1/media')
                ->assertOk()
                ->assertJsonStructure(['status', 'data', 'meta'])
                ->assertJsonCount(2, 'data');
        })->group('module:media');

        it('supports filters and includes the uploader', function (): void {
            loginAsUserRole();
            $media = MediaFactory::new()->createOne(['disk' => 'public']);

            $this->getJson("/api/v1/media?filter[disk]=public&include=uploadedBy&filter[mime_type]={$media->mime_type}")
                ->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'disk',
                            'visibility',
                            'mime_type',
                            'size',
                            'meta',
                            'url',
                            'uploaded_by',
                            'uploaded_by_user',
                        ],
                    ],
                ])
                ->assertJsonPath('data.0.uploaded_by_user.name', $media->uploadedBy?->name);
        })->group('module:media');

        it('denies users without the view permission', function (): void {
            loginAsUser();

            $this->getJson('/api/v1/media')
                ->assertForbidden()
                ->assertJsonStructure(['type', 'title', 'status', 'detail']);
        })->group('module:media');
    });

    describe('upload', function (): void {
        it('uploads a public file and returns a static url', function (): void {
            loginAsUserRole();

            $response = $this->postJson('/api/v1/media', [
                'file' => UploadedFile::fake()->create('photo.jpg', 100),
                'visibility' => 'public',
            ]);

            $response->assertCreated()
                ->assertJsonStructure([
                    'status',
                    'title',
                    'detail',
                    'data' => [
                        'id',
                        'disk',
                        'visibility',
                        'mime_type',
                        'size',
                        'meta',
                        'url',
                        'uploaded_by',
                    ],
                ])
                ->assertJsonPath('data.disk', 'public')
                ->assertJsonPath('data.visibility', 'public')
                ->assertJsonPath('data.meta.original_name', 'photo.jpg')
                ->assertJsonPath('data.url', fn (string $url) => str_contains($url, '/storage/'));

            $mediaId = $response->json('data.id');

            if (! is_string($mediaId)) {
                throw new LogicException('Media id must be a string.');
            }

            $media = Media::findOrFail($mediaId);
            Storage::disk('public')->assertExists($media->path);
        })->group('module:media');

        it('uploads a private file and returns a signed url with expiry', function (): void {
            loginAsUserRole();

            $response = $this->postJson('/api/v1/media', [
                'file' => UploadedFile::fake()->create('secret.txt', 100),
                'visibility' => 'private',
            ]);

            $response->assertCreated()
                ->assertJsonPath('data.disk', 'local')
                ->assertJsonPath('data.visibility', 'private')
                ->assertJsonPath('data.url', null)
                ->assertJsonPath('data.signed_url', fn (string $url) => str_contains($url, 'expiration='))
                ->assertJsonPath('data.expires_at', fn (string $expiresAt) => $expiresAt > now()->format('Y-m-d H:i:s'));

            $mediaId = $response->json('data.id');

            if (! is_string($mediaId)) {
                throw new LogicException('Media id must be a string.');
            }

            $media = Media::findOrFail($mediaId);
            Storage::disk('local')->assertExists($media->path);
        })->group('module:media');

        it('defaults to public visibility when omitted', function (): void {
            loginAsUserRole();

            $this->postJson('/api/v1/media', [
                'file' => UploadedFile::fake()->create('doc.pdf', 100),
            ])
                ->assertCreated()
                ->assertJsonPath('data.visibility', 'public');
        })->group('module:media');

        it('rejects an upload without a file', function (): void {
            loginAsUserRole();

            $this->postJson('/api/v1/media', ['visibility' => 'public'])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('file');
        })->group('module:media');

        it('rejects an upload larger than 10MB', function (): void {
            loginAsUserRole();

            $this->postJson('/api/v1/media', [
                'file' => UploadedFile::fake()->create('big.bin', 10241),
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('file');
        })->group('module:media');

        it('rejects an invalid visibility value', function (): void {
            loginAsUserRole();

            $this->postJson('/api/v1/media', [
                'file' => UploadedFile::fake()->create('doc.pdf', 100),
                'visibility' => 'secret',
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('visibility');
        })->group('module:media');

        it('denies uploads without the create permission', function (): void {
            $user = loginAsUser();

            $this->postJson('/api/v1/media', [
                'file' => UploadedFile::fake()->create('doc.pdf', 100),
            ])
                ->assertForbidden();

            expect(Media::where('uploaded_by', $user->id)->count())->toBe(0);
        })->group('module:media');
    });

    describe('show', function (): void {
        it('returns the media details with a signed url for private files', function (): void {
            loginAsUserRole();
            $media = MediaFactory::new()->createOne(['disk' => 'local']);

            $this->getJson("/api/v1/media/{$media->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $media->id)
                ->assertJsonPath('data.visibility', 'private')
                ->assertJsonPath('data.signed_url', fn (string $url) => str_contains($url, 'expiration='))
                ->assertJsonPath('data.expires_at', fn (string $expiresAt) => $expiresAt > now()->format('Y-m-d H:i:s'));
        })->group('module:media');

        it('returns 404 for a missing media', function (): void {
            loginAsUserRole();

            $this->getJson('/api/v1/media/01J00000000000000000000000')
                ->assertNotFound();
        })->group('module:media');
    });

    describe('delete', function (): void {
        it('allows the owner to delete their own media', function (): void {
            $user = loginAsUserRole();
            $media = MediaFactory::new()->createOne(['uploaded_by' => $user->id, 'disk' => 'public']);
            Storage::disk('public')->put($media->path, 'content');

            $this->deleteJson("/api/v1/media/{$media->id}")
                ->assertOk()
                ->assertJsonPath('status', 200);

            expect(Media::find($media->id))->toBeNull();
            Storage::disk('public')->assertMissing($media->path);
        })->group('module:media');

        it('allows a user with the delete permission to delete any media', function (): void {
            loginAsAdmin();
            $media = MediaFactory::new()->createOne(['disk' => 'local']);

            $this->deleteJson("/api/v1/media/{$media->id}")
                ->assertOk();
        })->group('module:media');

        it('denies deleting media owned by someone else without the delete permission', function (): void {
            loginAsUserRole();
            $media = MediaFactory::new()->createOne();

            $this->deleteJson("/api/v1/media/{$media->id}")
                ->assertForbidden()
                ->assertJsonStructure(['type', 'title', 'status', 'detail']);

            expect(Media::find($media->id))->not->toBeNull();
        })->group('module:media');
    });
});
