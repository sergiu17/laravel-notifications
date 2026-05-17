<?php

namespace App\Enums;

enum NotificationStatus: string
{
    case Pending = 'pending';
    case Invalid = 'invalid';
    case Cancelled = 'cancelled';
    case Queued = 'queued';
    case Failed = 'failed';
    case Processed = 'processed';
}
