<?php

declare(strict_types=1);

namespace Modules\Webhook\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Tenant\Models\Tenant;
use Modules\Webhook\Jobs\ProcessWebhookCallJob;
use Modules\Webhook\Models\Webhook;
use Modules\Webhook\Models\WebhookCall;
use Tests\TestCase;

class WebhookEnhancementTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_call_logs_headers_and_response(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['message' => 'ok'], 200, ['X-Test' => 'value']),
        ]);

        $tenant = Tenant::create(['id' => 'test-tenant']);

        $webhook = Webhook::create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'events' => ['user.created'],
            'is_active' => true,
        ]);

        $webhookCall = WebhookCall::create([
            'tenant_id' => $tenant->id,
            'webhook_id' => $webhook->id,
            'event' => 'user.created',
            'payload' => ['id' => '123'],
            'status' => 'pending',
        ]);

        (new ProcessWebhookCallJob($webhookCall))->handle();

        $webhookCall->refresh();

        $this->assertEquals('success', $webhookCall->status);
        $this->assertNotNull($webhookCall->request_headers);
        $this->assertNotNull($webhookCall->response_headers);
        $this->assertEquals(200, $webhookCall->status_code);
    }
}
