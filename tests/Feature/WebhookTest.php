<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Tenant\Models\Tenant;
use Modules\Webhook\Models\Webhook;
use Modules\Webhook\Services\WebhookService;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_is_dispatched_on_event(): void
    {
        Http::fake();

        $tenant = Tenant::create(['id' => 'test-tenant']);
        tenancy()->initialize($tenant);

        $webhook = Webhook::create([
            'tenant_id' => 'test-tenant',
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'events' => ['user.registered'],
            'secret' => 'super-secret',
        ]);

        $service = new WebhookService;
        $service->dispatch('user.registered', ['id' => 'user-123', 'email' => 'test@example.com']);

        // Check if webhook call is recorded
        $this->assertDatabaseHas('webhook_calls', [
            'tenant_id' => 'test-tenant',
            'event' => 'user.registered',
            'status' => 'success', // because Http::fake returns 200
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/webhook' &&
                   $request->header('X-Webhook-Event')[0] === 'user.registered' &&
                   $request->hasHeader('X-Webhook-Signature');
        });
    }
}
