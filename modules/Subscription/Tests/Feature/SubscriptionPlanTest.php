<?php

declare(strict_types=1);

namespace Modules\Subscription\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Subscription\Models\Subscription;
use Modules\Subscription\Models\SubscriptionPlan;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

describe('Subscription Feature Access', function () {
    it('denies access to features not included in the plan', function () {
        $tenant = Tenant::create(['id' => 'free-tenant']);

        tenancy()->initialize($tenant);
        $user = User::factory()->create();
        $token = $user->createToken('token')->plainTextToken;
        tenancy()->end();

        $freePlan = SubscriptionPlan::create([
            'name' => 'Free Plan',
            'slug' => 'free',
            'price' => 0,
            'billing_cycle' => 30,
            'features' => ['webhooks' => false],
        ]);

        Subscription::create([
            'tenant_id' => 'free-tenant',
            'plan_id' => $freePlan->id,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/webhooks', [
            'X-Tenant' => 'free-tenant',
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertForbidden()
            ->assertJsonPath('code', 'FEATURE_NOT_IN_PLAN');
    });

    it('grants access to features included in the plan', function () {
        $tenant = Tenant::create(['id' => 'pro-tenant']);

        tenancy()->initialize($tenant);
        $user = User::factory()->create();
        $token = $user->createToken('token')->plainTextToken;
        tenancy()->end();

        $proPlan = SubscriptionPlan::create([
            'name' => 'Pro Plan',
            'slug' => 'pro',
            'price' => 50,
            'billing_cycle' => 30,
            'features' => ['webhooks' => true],
        ]);

        Subscription::create([
            'tenant_id' => 'pro-tenant',
            'plan_id' => $proPlan->id,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/webhooks', [
            'X-Tenant' => 'pro-tenant',
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertSuccessful();
    });
});
