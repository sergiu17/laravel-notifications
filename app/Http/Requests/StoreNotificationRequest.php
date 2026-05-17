<?php

namespace App\Http\Requests;

use App\Enums\Channel;
use App\Enums\NotificationPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'recipient' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'channel' => ['required', Rule::enum(Channel::class)],
            'fallback_channel' => ['nullable', Rule::enum(Channel::class)],
            'priority' => ['nullable', Rule::enum(NotificationPriority::class)],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
