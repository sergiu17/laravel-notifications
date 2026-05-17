<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationStatusUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly Notification $notification) {}

    public function broadcastOn(): Channel
    {
        return new Channel('notifications');
    }

    public function broadcastAs(): string
    {
        return 'notification.status-updated';
    }

    /**
     * Payload sent over the WebSocket.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'batch_id' => $this->notification->batch_id,
            'channel' => $this->notification->channel,
            'recipient' => $this->notification->recipient,
            'status' => $this->notification->status?->value,
            'priority' => $this->notification->priority?->value,
            'updated_at' => $this->notification->updated_at?->toIso8601String(),
        ];
    }
}
