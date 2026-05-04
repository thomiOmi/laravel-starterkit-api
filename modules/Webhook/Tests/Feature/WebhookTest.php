<?php

declare(strict_types=1);

namespace Modules\Webhook\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Tenant\Models\Tenant;
use Modules\Webhook\Models\Webhook;
use Modules\Webhook\Services\WebhookService;

uses(RefreshDatabase::class);

describe('Webhook Dispatcher', function () {
    beforeEach(function () {
        Http::fake();
        $this->tenant = Tenant::create(['id' => 'test-tenant']);
        tenancy()->initialize($this->tenant);
    });

    it('dispatches webhook on specific events', function () {
        $webhook = Webhook::create([
            'tenant_id' => 'test-tenant',
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'events' => ['user.registered'],
            'secret' => 'super-secret',
        ]);

        $service = new WebhookService;
        $service->dispatch('user.registered', ['id' => 'user-123', 'email' => 'test@example.com']);

        $this->assertDatabaseHas('webhook_calls', [
            'tenant_id' => 'test-tenant',
            'event' => 'user.registered',
            'status' => 'success',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/webhook' &&
                   $request->header('X-Webhook-Event')[0] === 'user.registered' &&
                   $request->hasHeader('X-Webhook-Signature');
        });
    });
});
