<?php

namespace App\Http\Requests;

use App\Enums\Channel;
use App\Enums\NotificationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['nullable'],
            'batch_id' => ['nullable', 'ulid'],
            'status' => ['nullable', 'string', Rule::enum(NotificationStatus::class)],
            'channel' => ['nullable', 'string', Rule::enum(Channel::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after:from'],
        ];
    }
}
