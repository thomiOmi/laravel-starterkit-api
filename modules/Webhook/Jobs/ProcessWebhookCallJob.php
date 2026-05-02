<?php

declare(strict_types=1);

namespace Modules\Webhook\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Modules\Webhook\Models\Webhook;
use Modules\Webhook\Models\WebhookCall;

class ProcessWebhookCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [60, 600, 3600, 7200, 14400]; // 1m, 10m, 1h, 2h, 4h
    }

    public function __construct(
        protected WebhookCall $webhookCall
    ) {}

    public function handle(): void
    {
        /** @var Webhook|null $webhook */
        $webhook = $this->webhookCall->webhook;

        if (! $webhook || ! $webhook->is_active) {
            $this->webhookCall->update(['status' => 'cancelled']);

            return;
        }

        $headers = [
            'X-Webhook-Event' => $this->webhookCall->event,
            'X-Webhook-Signature' => $this->calculateSignature($webhook->secret, $this->webhookCall->payload),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $this->webhookCall->update([
            'status' => 'processing',
            'tries' => $this->webhookCall->tries + 1,
            'last_attempt_at' => now(),
            'request_headers' => $headers,
        ]);

        try {
            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->post($webhook->url, $this->webhookCall->payload);

            $this->webhookCall->update([
                'status_code' => $response->status(),
                'response_headers' => $response->headers(),
                'response_body' => $response->body(),
                'status' => $response->successful() ? 'success' : 'failed',
            ]);

            if (! $response->successful()) {
                throw new \Exception('Webhook request failed with status '.$response->status());
            }
        } catch (\Exception $e) {
            $this->webhookCall->update([
                'status' => 'failed',
                'exception' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function calculateSignature(?string $secret, array $payload): string
    {
        if (! $secret) {
            return '';
        }

        return hash_hmac('sha256', (string) json_encode($payload), $secret);
    }
}
