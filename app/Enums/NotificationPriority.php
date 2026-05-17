<?php

namespace App\Enums;

enum NotificationPriority: string
{
    case High = 'high';
    case Default = 'default';
    case Low = 'low';
}
