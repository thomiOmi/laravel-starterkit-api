<?php

declare(strict_types=1);

namespace Modules\Media\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
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

        $known = $this->knownPathsAndDirectories();
        $dbSet = $known['paths'];

        $orphans = [];
        $ignored = [];
        $scanDirectories = [...$this->scanDirectories(), ...$known['directories']];
        $scanRoot = MediaPrefix::prefix();

        foreach ($storage->allFiles($scanRoot === '' ? '/' : $scanRoot) as $file) {
            // Skip .gitignore and hidden files.
            if (basename($file) === '.gitignore' || str_starts_with(basename($file), '.')) {
                continue;
            }

            if (isset($dbSet[$file])) {
                continue;
            }

            // Derived on-demand conversions are regenerable cache — exclude from orphan detection (Spatie pattern).
            // The fixed root covers the default layout (including stray
            // files without an id level); per-media directories cover
            // custom path generators.
            $derivedPrefix = MediaPrefix::join('conversions/derived');

            if (str_starts_with($file, $derivedPrefix.'/') || $this->isUnderScannedDirectories($file, $known['derived'])) {
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

            Log::warning('Media orphan files detected.', [
                'count' => count($orphans),
                'destructive' => $destructive,
                'files' => $orphans,
            ]);
        }

        // Also check for DB records with missing files.
        $missing = 0;
        $missingIds = [];

        foreach (Media::query()->cursor() as $media) {
            $path = $media->getPath();

            if (! is_string($path) || ! $storage->exists($path)) {
                $missing++;
                $missingIds[] = (string) $media->id;
                $this->warn(sprintf('Missing file for media %s: %s', $media->id, $path ?? 'null'));
            }
        }

        if ($missing > 0) {
            $this->warn(sprintf('Found %d DB records with missing files.', $missing));

            Log::warning('Media records with missing files.', [
                'count' => $missing,
                'media_ids' => $missingIds,
            ]);
        }

        return self::SUCCESS;
    }

    /**
     * Directories this command is allowed to scan: known collection
     * directories plus the conversions root, all under the optional
     * prefix. Anything else on the disk is ignored. The derived
     * conversions cache (conversions/derived/) is deliberately
     * excluded: derived files are regenerable and their names cannot
     * be enumerated from the database.
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
        return array_any($scanDirectories, fn (string $directory): bool => $file === $directory || str_starts_with($file, $directory.'/'));
    }

    /**
     * Storage paths the database knows about, plus every derivative
     * directory the media path generators own. Writers build all three
     * through the same seams (see Media::getConversionsDirectory()),
     * so cleanup reads and deletes exactly what they wrote.
     *
     * @return array{paths: array<string, true>, directories: array<int, string>, derived: array<int, string>}
     */
    private function knownPathsAndDirectories(): array
    {
        $known = [];
        $directories = [];
        $derived = [];

        foreach (Media::query()->cursor() as $media) {
            $path = $media->getPath();

            if (is_string($path)) {
                $known[$path] = true;
            }

            $directories[] = $media->getConversionsDirectory();
            $directories[] = $media->getResponsiveImagesDirectory();
            $derived[] = $media->getDerivedConversionsDirectory();

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

        return [
            'paths' => $known,
            'directories' => array_values(array_unique($directories)),
            'derived' => array_values(array_unique($derived)),
        ];
    }
}
