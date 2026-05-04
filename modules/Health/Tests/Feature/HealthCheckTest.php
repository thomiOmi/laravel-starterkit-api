<?php

declare(strict_types=1);

namespace Modules\Health\Tests\Feature;

describe('Health Check', function () {
    it('returns ok status for all services', function () {
        $response = $this->getJson('/api/v1/health');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'status',
                'timestamp',
                'services' => [
                    'database',
                    'cache',
                    'storage',
                ],
            ])
            ->assertJsonPath('status', 'ok');
    });
});
