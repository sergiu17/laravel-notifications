<?php

namespace App\Models;

use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Observers\NotificationObserver;
use App\Traits\HasFilters;
use Database\Factories\NotificationFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(NotificationObserver::class)]
class Notification extends Model
{
    /** @use HasFactory<NotificationFactory> */
    use HasFactory;

    use HasFilters;
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'batch_id',
        'channel',
        'recipient',
        'content',
        'scheduled_at',
        '∂',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => NotificationPriority::class,
            'status' => NotificationStatus::class,
        ];
    }

    /**
     * @return string[]
     */
    public function uniqueIds(): array
    {
        return ['batch_id'];
    }
}
