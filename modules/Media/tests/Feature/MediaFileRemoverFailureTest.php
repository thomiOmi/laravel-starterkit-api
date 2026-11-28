<?php

declare(strict_types=1);

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Support\FileRemover\DefaultFileRemover;
use Modules\Media\Support\FileRemover\MediaFilesSnapshot;

covers(DefaultFileRemover::class);

it('logs a failed file target and still processes the remaining targets', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('ok.txt', 'content');

    $warnings = [];

    Log::shouldReceive('warning')->andReturnUsing(
        function (string $message, array $context) use (&$warnings): void {
            $warnings[] = ['message' => $message, 'context' => $context];
        },
    );

    $broken = Mockery::mock(FilesystemAdapter::class);
    $broken->shouldReceive('delete')->once()->with('fail.txt')->andThrow(new Exception('storage down'));
    $broken->shouldReceive('deleteDirectory')->once()->with('derived')->andReturn(true);
    Storage::set('broken', $broken);

    $snapshot = new MediaFilesSnapshot('01TEST', [
        ['path' => 'fail.txt', 'disk' => 'broken'],
        ['path' => 'ok.txt', 'disk' => 'public'],
    ], [
        ['directory' => 'derived', 'disk' => 'broken'],
    ]);

    resolve(DefaultFileRemover::class)->removeCapturedFiles($snapshot);

    expect($warnings)->toHaveCount(1)
        ->and($warnings[0]['message'])->toBe('Media file deletion failed.')
        ->and($warnings[0]['context']['media_id'])->toBe('01TEST')
        ->and($warnings[0]['context']['path'])->toBe('fail.txt')
        ->and($warnings[0]['context']['disk'])->toBe('broken')
        ->and($warnings[0]['context']['error'])->toBe('storage down');

    Storage::disk('public')->assertMissing('ok.txt');
});

it('logs a false delete return value and still processes the remaining targets', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('ok.txt', 'content');

    $warnings = [];

    Log::shouldReceive('warning')->andReturnUsing(
        function (string $message, array $context) use (&$warnings): void {
            $warnings[] = ['message' => $message, 'context' => $context];
        },
    );

    $broken = Mockery::mock(FilesystemAdapter::class);
    $broken->shouldReceive('delete')->once()->with('fail.txt')->andReturn(false);
    Storage::set('broken', $broken);

    $snapshot = new MediaFilesSnapshot('01TEST', [
        ['path' => 'fail.txt', 'disk' => 'broken'],
        ['path' => 'ok.txt', 'disk' => 'public'],
    ], []);

    resolve(DefaultFileRemover::class)->removeCapturedFiles($snapshot);

    expect($warnings)->toHaveCount(1)
        ->and($warnings[0]['message'])->toBe('Media file deletion failed.')
        ->and($warnings[0]['context']['media_id'])->toBe('01TEST')
        ->and($warnings[0]['context']['path'])->toBe('fail.txt')
        ->and($warnings[0]['context']['disk'])->toBe('broken')
        ->and($warnings[0]['context']['error'])->toBe('delete returned false');

    Storage::disk('public')->assertMissing('ok.txt');
});

it('logs a false deleteDirectory return value and continues', function (): void {
    $warnings = [];

    Log::shouldReceive('warning')->andReturnUsing(
        function (string $message, array $context) use (&$warnings): void {
            $warnings[] = ['message' => $message, 'context' => $context];
        },
    );

    $broken = Mockery::mock(FilesystemAdapter::class);
    $broken->shouldReceive('deleteDirectory')->once()->with('derived')->andReturn(false);
    Storage::set('broken', $broken);

    resolve(DefaultFileRemover::class)->removeCapturedFiles(new MediaFilesSnapshot('01TEST', [], [
        ['directory' => 'derived', 'disk' => 'broken'],
    ]));

    expect($warnings)->toHaveCount(1)
        ->and($warnings[0]['context']['path'])->toBe('derived')
        ->and($warnings[0]['context']['error'])->toBe('deleteDirectory returned false');
});

it('logs directory failures per target and continues', function (): void {
    $warnings = [];

    Log::shouldReceive('warning')->andReturnUsing(
        function (string $message, array $context) use (&$warnings): void {
            $warnings[] = ['message' => $message, 'context' => $context];
        },
    );

    $broken = Mockery::mock(FilesystemAdapter::class);
    $broken->shouldReceive('deleteDirectory')->once()->with('derived/one')->andThrow(new Exception('directory down'));
    $broken->shouldReceive('deleteDirectory')->once()->with('derived/two')->andReturn(true);
    Storage::set('broken', $broken);

    $snapshot = new MediaFilesSnapshot('01TEST', [], [
        ['directory' => 'derived/one', 'disk' => 'broken'],
        ['directory' => 'derived/two', 'disk' => 'broken'],
    ]);

    resolve(DefaultFileRemover::class)->removeCapturedFiles($snapshot);

    expect($warnings)->toHaveCount(1)
        ->and($warnings[0]['message'])->toBe('Media file deletion failed.')
        ->and($warnings[0]['context']['media_id'])->toBe('01TEST')
        ->and($warnings[0]['context']['path'])->toBe('derived/one')
        ->and($warnings[0]['context']['disk'])->toBe('broken')
        ->and($warnings[0]['context']['error'])->toBe('directory down');
});
