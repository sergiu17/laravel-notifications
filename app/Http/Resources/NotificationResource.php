<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public static $wrap = false;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_id' => $this->batch_id,
            'channel' => $this->channel,
            'recipient' => $this->recipient,
            'content' => $this->content,
            'scheduled_at' => $this->scheduled_at,
            'priority' => $this->priority,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
