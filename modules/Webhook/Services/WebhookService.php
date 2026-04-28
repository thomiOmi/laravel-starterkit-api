<?php

declare(strict_types=1);

namespace Modules\Webhook\Services;

use Modules\Webhook\Jobs\ProcessWebhookCallJob;
use Modules\Webhook\Models\Webhook;
use Modules\Webhook\Models\WebhookCall;

class WebhookService
{
    /**
     * Dispatch webhooks for a specific event and payload.
     */
    public function dispatch(string $event, array $payload, ?string $tenantId = null): void
    {
        $tenantId = $tenantId ?: tenant('id');

        if (! $tenantId) {
            return;
        }

        $webhooks = Webhook::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereJsonContains('events', $event)
            ->get();

        foreach ($webhooks as $webhook) {
            $webhookCall = WebhookCall::create([
                'tenant_id' => $tenantId,
                'webhook_id' => $webhook->id,
                'event' => $event,
                'payload' => $payload,
                'status' => 'pending',
            ]);

            ProcessWebhookCallJob::dispatch($webhookCall);
        }
    }
}
