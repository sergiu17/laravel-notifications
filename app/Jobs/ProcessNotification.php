<?php

namespace App\Jobs;

use App\Enums\NotificationStatus;
use App\Events\NotificationStatusUpdated;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

class ProcessNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [3, 5];

    public function __construct(public readonly Notification $notification) {}

    public function handle(): void
    {
        $channel = $this->notification->channel;
        $rateKey = "rate:notification:{$channel}";
        $maxPerSecond = config("notifications.rate_limits.{$channel}", 100);

        $executed = RateLimiter::attempt(
            key: $rateKey,
            maxAttempts: $maxPerSecond,
            callback: fn () => $this->send(),
            decaySeconds: 1,
        );

        if (! $executed) {
            Log::debug('notification.rate_limited', $this->logContext([
                'rate_key' => $rateKey,
                'limit' => $maxPerSecond,
            ]));

            $this->release(1);
        }
    }

    private function send(): void
    {
        $response = $this->callProvider($this->notification);

        if ($response->successful()) {
            $this->transition(NotificationStatus::Processed, [
                'provider_message_id' => $response->json('messageId'),
                'http_status' => $response->status(),
            ]);

            return;
        }

        if ($response->clientError()) {
            $this->transition(NotificationStatus::Failed, [
                'reason' => "Provider rejected: HTTP {$response->status()}",
                'http_status' => $response->status(),
                'body' => $response->body(),
            ]);

            $this->fail(new RuntimeException(
                "Permanent failure for {$this->notification->id}: HTTP {$response->status()}",
            ));

            return;
        }

        // 5xx — log and re-throw so Laravel applies $this->backoff and retries.
        Log::warning('notification.retrying', $this->logContext([
            'reason' => 'provider server error',
            'http_status' => $response->status(),
        ]));

        throw new RuntimeException(
            "Provider error for {$this->notification->id}: HTTP {$response->status()}",
        );
    }

    private function callProvider(Notification $notification): Response
    {
        try {
            return Http::timeout(config('notifications.timeout', 5))
                ->acceptJson()
                ->asJson()
                ->post(config('notifications.webhook_url'), [
                    'to' => $notification->recipient,
                    'channel' => $notification->channel,
                    'content' => $notification->content,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('notification.connection_error', $this->logContext([
                'error' => $e->getMessage(),
            ]));

            throw new RuntimeException(
                "Connection error reaching provider: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    private function transition(NotificationStatus $status, array $context = []): void
    {
        $this->notification->update(['status' => $status]);

        // Push live update to dashboard subscribers via Reverb.
        NotificationStatusUpdated::dispatch($this->notification);

        $level = match ($status) {
            NotificationStatus::Processed => 'info',
            NotificationStatus::Failed => 'warning',
            default => 'debug',
        };

        Log::{$level}("notification.{$status->value}", $this->logContext($context));
    }

    private function logContext(array $extra = []): array
    {
        return [
            'notification_id' => $this->notification->id,
            'batch_id' => $this->notification->batch_id,
            'channel' => $this->notification->channel,
            'priority' => $this->notification->priority?->value,
            'attempt' => $this->attempts(),
            ...$extra,
        ];
    }
}
