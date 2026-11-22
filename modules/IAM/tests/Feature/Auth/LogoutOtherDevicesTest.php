<?php

declare(strict_types=1);

use Illuminate\Contracts\Hashing\Hasher;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Controllers\V1\LogoutOtherDevicesController;

covers(LogoutOtherDevicesController::class);

describe('POST /api/v1/auth/devices/logout-others', function (): void {
    it('revokes every other session and keeps the current one', function (): void {
        $user = UserFactory::new()->createOne(['password' => 'my-password']);
        $current = $user->createToken('current');
        $user->createToken('old-phone');
        $user->createToken('old-tablet');

        $response = $this->withHeader('Authorization', 'Bearer '.$current->plainTextToken)
            ->postJson('/api/v1/auth/devices/logout-others', ['current_password' => 'my-password']);

        assertSuccessResponse($response, 200);
        expect($user->tokens()->count())->toBe(1)
            ->and($user->tokens()->first()?->name)->toBe('current');
    });

    it('skips the hash check when the current password fails a cheap rule', function (): void {
        $user = UserFactory::new()->createOne(['password' => 'my-password']);
        $token = $user->createToken('current');
        $hasher = new class implements Hasher
        {
            public int $checks = 0;

            /** @return array<string, mixed> */
            public function info($hashedValue): array
            {
                return [];
            }

            /** @param  array<string, mixed>  $options */
            public function make($value, array $options = []): string
            {
                return (string) $value;
            }

            /** @param  array<string, mixed>  $options */
            public function check($value, $hashedValue, array $options = []): bool
            {
                $this->checks++;

                return false;
            }

            /** @param  array<string, mixed>  $options */
            public function needsRehash($hashedValue, array $options = []): bool
            {
                return false;
            }
        };
        $this->app->instance('hash', $hasher);

        $tooLong = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/auth/devices/logout-others', ['current_password' => str_repeat('a', 256)]);

        assertProblemResponse($tooLong, 422, 'validation');
        $tooLong->assertJsonValidationErrors(['current_password']);
        expect($hasher->checks)->toBe(0);

        $wrong = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/auth/devices/logout-others', ['current_password' => 'wrong']);

        assertProblemResponse($wrong, 422, 'validation');
        $wrong->assertJsonValidationErrors(['current_password']);
        expect($hasher->checks)->toBe(1);
    });

    it('rejects a wrong password', function (): void {
        $user = UserFactory::new()->createOne(['password' => 'my-password']);
        $token = $user->createToken('current');
        $user->createToken('other');

        $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/auth/devices/logout-others', ['current_password' => 'wrong']);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['current_password']);
        expect($user->tokens()->count())->toBe(2);
    });
});
