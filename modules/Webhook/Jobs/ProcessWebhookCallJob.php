<?php

declare(strict_types=1);

namespace Modules\Webhook\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Modules\Webhook\Models\WebhookCall;

class ProcessWebhookCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected WebhookCall $webhookCall
    ) {}

    public function handle(): void
    {
        $webhook = $this->webhookCall->webhook;

        if (! $webhook || ! $webhook->is_active) {
            $this->webhookCall->update(['status' => 'cancelled']);

            return;
        }

        $this->webhookCall->update([
            'status' => 'processing',
            'tries' => $this->webhookCall->tries + 1,
            'last_attempt_at' => now(),
        ]);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Webhook-Event' => $this->webhookCall->event,
                    'X-Webhook-Signature' => $this->calculateSignature($webhook->secret, $this->webhookCall->payload),
                ])
                ->post($webhook->url, $this->webhookCall->payload);

            $this->webhookCall->update([
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'status' => $response->successful() ? 'success' : 'failed',
            ]);
        } catch (\Exception $e) {
            $this->webhookCall->update([
                'status' => 'failed',
                'response_body' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function calculateSignature(?string $secret, array $payload): string
    {
        if (! $secret) {
            return '';
        }

        return hash_hmac('sha256', json_encode($payload), $secret);
    }
}
