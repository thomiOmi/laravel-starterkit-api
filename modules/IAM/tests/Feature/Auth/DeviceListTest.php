<?php

declare(strict_types=1);

use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Controllers\V1\DeviceListController;

covers(DeviceListController::class);

describe('GET /api/v1/auth/devices', function () {
    it('lists the authenticated user devices with the current flag', function () {
        $user = UserFactory::new()->createOne();
        $current = $user->createToken('current');
        $user->createToken('second');

        $response = $this->withHeader('Authorization', 'Bearer '.$current->plainTextToken)
            ->getJson('/api/v1/auth/devices?page[size]=10&page[number]=1');

        assertSuccessResponse($response, 200);
        $rows = $response->json('data');
        expect($rows)->toBeArray();

        $currentCount = 0;

        foreach (is_array($rows) ? $rows : [] as $row) {
            if (is_array($row) && ($row['is_current'] ?? false) === true) {
                $currentCount++;
                expect($row['name'] ?? null)->toBe('current');
            }
        }

        expect($currentCount)->toBe(1);
    });

    it('rejects unauthenticated requests', function () {
        $this->getJson('/api/v1/auth/devices')->assertUnauthorized();
    });
});
