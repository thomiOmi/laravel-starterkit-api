<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Schedule;
use Modules\Media\Console\Commands\MediaCleanupCommand;

covers(MediaCleanupCommand::class);

describe('console schedule', function (): void {
    it('schedules a daily dry-run of media:cleanup', function (): void {
        $event = collect(Schedule::events())
            ->first(fn (Event $scheduled): bool => str_contains((string) ($scheduled->command ?? ''), 'media:cleanup'));

        expect($event)->toBeInstanceOf(Event::class)
            ->and($event?->expression)->toBe('0 3 * * *');
    });
});
