<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Subscription\Models\Subscription;
use Modules\Subscription\Models\SubscriptionPlan;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Tests\TestCase;

class SubscriptionPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_cannot_access_feature_without_proper_plan(): void
    {
        // 1. Create a tenant
        $tenant = Tenant::create(['id' => 'free-tenant']);

        // 2. Create a user for this tenant
        tenancy()->initialize($tenant);
        $user = User::factory()->create();
        $token = $user->createToken('token')->plainTextToken;
        tenancy()->end();

        // 3. Create a plan that DOES NOT have webhooks
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

        // 4. Try to access webhooks API -> Should be 403
        $response = $this->getJson('/api/v1/webhooks', [
            'X-Tenant' => 'free-tenant',
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('code', 'FEATURE_NOT_IN_PLAN');
    }

    public function test_tenant_can_access_feature_with_proper_plan(): void
    {
        // 1. Create a tenant
        $tenant = Tenant::create(['id' => 'pro-tenant']);

        // 2. Create a user for this tenant
        tenancy()->initialize($tenant);
        $user = User::factory()->create();
        $token = $user->createToken('token')->plainTextToken;
        tenancy()->end();

        // 3. Create a plan that HAS webhooks
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

        // 4. Try to access webhooks API -> Should be 200
        $response = $this->getJson('/api/v1/webhooks', [
            'X-Tenant' => 'pro-tenant',
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(200);
    }
}
