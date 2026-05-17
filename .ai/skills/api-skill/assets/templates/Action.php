<?php

declare(strict_types=1);

namespace Modules\{Module}\Actions;

use Illuminate\Database\DatabaseManager;
use Modules\{Module}\Models\{Resource};
use Modules\{Module}\Payloads\V1\{Action}{Resource}Payload;

/**
 * Class {Action}{Resource}Action
 *
 * @package Modules\{Module}\Actions
 */
final readonly class {Action}{Resource}Action
{
    /**
     * {Action}{Resource}Action constructor.
     *
     * @param DatabaseManager $database
     */
    public function __construct(
        private DatabaseManager $database,
    ) {}

    /**
     * Handle the action.
     *
     * @param {Action}{Resource}Payload $payload
     * @return {Resource}
     */
    public function handle({Action}{Resource}Payload $payload): {Resource}
    {
        return $this->database->transaction(function () use ($payload): {Resource} {
            // Logic here
            return {Resource}::create($payload->toArray());
        });
    }
}
