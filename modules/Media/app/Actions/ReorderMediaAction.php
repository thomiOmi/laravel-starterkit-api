<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Media\Models\Media;

/**
 * Reorder media within a collection for a given owner.
 */
final readonly class ReorderMediaAction
{
    /**
     * @param  array<int, string>  $orderedIds  Ordered list of media ULIDs.
     */
    public function handle(Model $owner, string $collection, array $orderedIds): void
    {
        if ($orderedIds === []) {
            return;
        }

        /** @var array<int, string> $existingIds */
        $existingIds = Media::query()
            ->where('model_type', $owner->getMorphClass())
            ->where('model_id', $owner->getKey())
            ->where('collection_name', $collection)
            ->pluck('id')
            ->all();

        /** @var array<string, int> $existingSet */
        $existingSet = array_flip($existingIds);

        foreach ($orderedIds as $id) {
            if (! isset($existingSet[$id])) {
                throw new InvalidArgumentException(__('validation.media_unavailable'));
            }
        }

        DB::transaction(function () use ($owner, $collection, $orderedIds): void {
            foreach ($orderedIds as $index => $id) {
                Media::query()
                    ->where('model_type', $owner->getMorphClass())
                    ->where('model_id', $owner->getKey())
                    ->where('collection_name', $collection)
                    ->where('id', $id)
                    ->update(['order_column' => $index + 1]);
            }
        });
    }
}
