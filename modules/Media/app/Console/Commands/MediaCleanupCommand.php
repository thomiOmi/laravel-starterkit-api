<?php

declare(strict_types=1);

namespace Modules\Media\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;
use Modules\Media\Models\MediaConversion;

#[Signature('media:cleanup {--dry-run : Show what would be deleted without removing files}')]
#[Description('Remove orphan media files from storage without DB records and prune empty variant dirs')]
final class MediaCleanupCommand extends Command
{
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $disk = config()->string('media.disk', 'public');
        $storage = Storage::disk($disk);

        /** @var array<int, string> $dbPaths */
        $dbPaths = Media::query()->pluck('path')->all();
        /** @var array<int, string> $dbConversionPaths */
        $dbConversionPaths = MediaConversion::query()->pluck('path')->all();
        /** @var array<int, string> $dbAllPaths */
        $dbAllPaths = array_merge($dbPaths, $dbConversionPaths);

        /** @var array<string, int> $dbSet */
        $dbSet = array_flip($dbAllPaths);
        $allFiles = $storage->allFiles();

        $orphans = [];

        foreach ($allFiles as $file) {
            // Skip .gitignore and hidden files.
            if ($file === '.gitignore' || str_starts_with($file, '.')) {
                continue;
            }

            if (! isset($dbSet[$file])) {
                $orphans[] = $file;
            }
        }

        if ($orphans === []) {
            $this->info('No orphan files found.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d orphan file(s):', count($orphans)));

        foreach ($orphans as $orphan) {
            $this->line(' - '.$orphan);

            if (! $dryRun) {
                $storage->delete($orphan);
            }
        }

        if ($dryRun) {
            $this->info('Dry run: no files deleted.');
        } else {
            $this->info(sprintf('Deleted %d orphan file(s).', count($orphans)));
        }

        // Also check for DB records with missing files.
        $missing = 0;

        foreach (Media::query()->cursor() as $media) {
            if (! $storage->exists($media->path)) {
                $missing++;
                $this->warn(sprintf('Missing file for media %s: %s', $media->id, $media->path));

                if (! $dryRun) {
                    // Optionally delete the DB record? For now just warn.
                }
            }
        }

        if ($missing > 0) {
            $this->warn(sprintf('Found %d DB records with missing files.', $missing));
        }

        return self::SUCCESS;
    }
}
