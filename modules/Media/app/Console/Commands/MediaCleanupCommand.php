<?php

declare(strict_types=1);

namespace Modules\Media\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;
use Modules\Media\Models\MediaConversion;
use Modules\Media\Support\MediaPrefix;

#[Signature('media:cleanup {--force : Actually delete orphan files (default is dry-run)} {--dry-run : Show what would be deleted without removing files}')]
#[Description('Report orphan media files without DB records (deletes only with --force)')]
final class MediaCleanupCommand extends Command
{
    public function handle(): int
    {
        $destructive = (bool) $this->option('force') && ! (bool) $this->option('dry-run');
        $disk = config()->string('media.disk', 'public');
        $storage = Storage::disk($disk);

        $dbSet = $this->knownPaths();

        $orphans = [];
        $ignored = [];
        $scanDirectories = $this->scanDirectories();
        $scanRoot = MediaPrefix::prefix();

        foreach ($storage->allFiles($scanRoot === '' ? '/' : $scanRoot) as $file) {
            // Skip .gitignore and hidden files.
            if (basename($file) === '.gitignore' || str_starts_with(basename($file), '.')) {
                continue;
            }

            if (isset($dbSet[$file])) {
                continue;
            }

            if ($this->isUnderScannedDirectories($file, $scanDirectories)) {
                $orphans[] = $file;
            } else {
                $ignored[] = $file;
            }
        }

        if ($ignored !== []) {
            $this->warn(sprintf('Ignored %d file(s) outside known media paths.', count($ignored)));
        }

        if ($orphans === []) {
            $this->info('No orphan files found.');
        } else {
            $this->info(sprintf('Found %d orphan file(s):', count($orphans)));

            foreach ($orphans as $orphan) {
                $this->line(' - '.$orphan);

                if ($destructive) {
                    $storage->delete($orphan);
                }
            }

            if ($destructive) {
                $this->info(sprintf('Deleted %d orphan file(s).', count($orphans)));
            } else {
                $this->info('Dry run: no files deleted. Pass --force to delete.');
            }
        }

        // Also check for DB records with missing files.
        $missing = 0;

        foreach (Media::query()->cursor() as $media) {
            $path = $media->getPath();

            if (! is_string($path) || ! $storage->exists($path)) {
                $missing++;
                $this->warn(sprintf('Missing file for media %s: %s', $media->id, $path ?? 'null'));
            }
        }

        if ($missing > 0) {
            $this->warn(sprintf('Found %d DB records with missing files.', $missing));
        }

        return self::SUCCESS;
    }

    /**
     * Directories this command is allowed to scan: known collection
     * directories plus the conversions root, all under the optional
     * prefix. Anything else on the disk is ignored. The variants/
     * cache is deliberately excluded: variant files are regenerable
     * and their names cannot be enumerated from the database.
     *
     * @return array<int, string>
     */
    private function scanDirectories(): array
    {
        $directories = [];

        foreach (Media::query()->distinct()->pluck('collection_name')->all() as $collection) {
            if (is_string($collection) && $collection !== '') {
                $directories[] = MediaPrefix::directory($collection);
            }
        }

        $directories[] = MediaPrefix::join('conversions');

        return array_values(array_unique($directories));
    }

    /**
     * @param  array<int, string>  $scanDirectories
     */
    private function isUnderScannedDirectories(string $file, array $scanDirectories): bool
    {
        foreach ($scanDirectories as $directory) {
            if ($file === $directory || str_starts_with($file, $directory.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, true>
     */
    private function knownPaths(): array
    {
        $known = [];

        foreach (Media::query()->cursor() as $media) {
            $path = $media->getPath();

            if (is_string($path)) {
                $known[$path] = true;
            }

            $responsive = $media->responsive_images;

            if (! is_array($responsive)) {
                continue;
            }

            foreach ($responsive as $info) {
                if ($info['path'] !== '') {
                    $known[$info['path']] = true;
                }
            }
        }

        foreach (MediaConversion::query()->pluck('path')->all() as $path) {
            if (is_string($path) && $path !== '') {
                $known[$path] = true;
            }
        }

        return $known;
    }
}
