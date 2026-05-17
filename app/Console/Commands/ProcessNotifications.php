<?php

namespace App\Console\Commands;

use App\Enums\NotificationStatus;
use App\Jobs\ProcessNotification;
use App\Models\Notification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:process-notifications')]
#[Description('Process notifications')]
class ProcessNotifications extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        Notification::query()
            ->whereIn('status', [NotificationStatus::Pending->value, NotificationStatus::Failed->value])
            ->where(function ($query) {
                $query
                    ->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->chunkById(500, function ($notifications) {
                foreach ($notifications as $notification) {
                    ProcessNotification::dispatch($notification)->onQueue($notification->priority->value);
                }
            });

        return self::SUCCESS;
    }
}
