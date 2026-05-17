<?php

namespace App\Http\Requests;

use App\Enums\Channel;
use App\Enums\NotificationPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNotificationBatchRequest extends FormRequest
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
            'notifications' => ['required', 'array', 'min:1', 'max:1000'],
            'notifications.*.recipient' => ['required', 'string', 'max:255'],
            'notifications.*.content' => ['required', 'string'],
            'notifications.*.channel' => ['required', Rule::enum(Channel::class)],
            'notifications.*.fallback_channel' => ['nullable', Rule::enum(Channel::class)],
            'notifications.*.priority' => ['nullable', Rule::enum(NotificationPriority::class)],
            'notifications.*.scheduled_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
