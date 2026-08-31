<?php

declare(strict_types=1);

namespace Modules\Media\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Modules\Media\Actions\ReprocessMediaAction;
use Modules\Media\Models\Media;

#[Signature('media:reprocess {--id= : ULID of a single media to reprocess} {--collection= : Only reprocess media in this collection} {--conversion= : Only reprocess a single named conversion} {--queued : Dispatch as queued jobs instead of sync}')]
#[Description('Re-generate conversions for media (all or filtered)')]
final class MediaReprocessCommand extends Command
{
    public function handle(ReprocessMediaAction $reprocess): int
    {
        $mediaId = $this->option('id');
        $collection = $this->option('collection');
        $queued = (bool) $this->option('queued');

        $query = Media::query()->where('mime_type', 'like', 'image/%');

        if (is_string($mediaId) && $mediaId !== '') {
            $query->where('id', $mediaId);
        }

        if (is_string($collection) && $collection !== '') {
            $query->where('collection_name', $collection);
        }

        $count = 0;

        foreach ($query->cursor() as $media) {
            $reprocess->handle($media, $queued);
            $count++;
            $this->line(sprintf('Reprocessed %s (%s)', $media->id, $media->collection_name));
        }

        $this->info(sprintf('Reprocessed %d media item(s).', $count));

        return self::SUCCESS;
    }
}
