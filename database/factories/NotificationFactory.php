<?php

namespace Database\Factories;

use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'batch_id' => (string) Str::ulid(),
            'channel' => 'email',
            'recipient' => fake()->safeEmail(),
            'content' => fake()->sentence(),
            'priority' => NotificationPriority::Default,
            'status' => NotificationStatus::Pending,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => NotificationStatus::Pending]);
    }

    public function processed(): static
    {
        return $this->state(['status' => NotificationStatus::Processed]);
    }

    public function failed(): static
    {
        return $this->state(['status' => NotificationStatus::Failed]);
    }
}
