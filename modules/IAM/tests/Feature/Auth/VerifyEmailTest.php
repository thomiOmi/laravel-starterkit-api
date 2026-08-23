<?php

declare(strict_types=1);

use App\Enums\UserStatusEnum;
use Illuminate\Support\Facades\URL;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Controllers\V1\VerifyEmailController;

covers(VerifyEmailController::class);

describe('GET /api/v1/auth/email/verify/{id}/{hash}', function () {
    it('marks the user verified and active with a valid signed url', function () {
        $user = UserFactory::new()->unverified()->createOne(['status' => UserStatusEnum::Pending]);

        $url = URL::temporarySignedRoute(
            'api.v1.iam.auth.verification.verify',
            now()->addMinutes(10),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user, 'sanctum')->getJson($url);

        assertSuccessResponse($response, 200);
        expect($user->fresh()?->email_verified_at)->not->toBeNull()
            ->and($user->fresh()?->status)->toBe(UserStatusEnum::Active);
    });

    it('rejects a mismatched hash', function () {
        $user = UserFactory::new()->unverified()->createOne();

        $url = URL::temporarySignedRoute(
            'api.v1.iam.auth.verification.verify',
            now()->addMinutes(10),
            ['id' => $user->id, 'hash' => sha1('someone-else@example.com')]
        );

        $this->actingAs($user, 'sanctum')->getJson($url)->assertForbidden();
    });

    it('rejects unsigned urls', function () {
        $user = UserFactory::new()->unverified()->createOne();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/auth/email/verify/{$user->id}/".sha1($user->email))
            ->assertForbidden();
    });
});
