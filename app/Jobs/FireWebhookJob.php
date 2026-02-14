<?php

namespace App\Jobs;

use App\Models\Webhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class FireWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $companyId,
        public string $event,
        public array $payload
    ) {}

    public function handle(): void
    {
        $webhooks = Webhook::withoutGlobalScope(\App\Scopes\CompanyScope::class)
            ->where('company_id', $this->companyId)
            ->where('event', $this->event)
            ->where('enabled', true)
            ->get();
        foreach ($webhooks as $webhook) {
            try {
                $headers = ['Content-Type' => 'application/json', 'X-Webhook-Event' => $this->event];
                if ($webhook->secret) {
                    $headers['X-Webhook-Signature'] = 'sha256=' . hash_hmac('sha256', json_encode($this->payload), $webhook->secret);
                }
                Http::withHeaders($headers)->timeout(10)->post($webhook->url, $this->payload);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
