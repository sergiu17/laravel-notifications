<?php

namespace App\Http\Controllers;

use App\Enums\Channel;
use App\Enums\NotificationStatus;
use App\Http\Requests\NotificationRequest;
use App\Models\Notification;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(NotificationRequest $request): View
    {
        $notifications = Notification::filter($request->validated())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('dashboard', [
            'notifications' => $notifications,
            'filters' => $request->validated(),
            'channels' => Channel::cases(),
            'statuses' => NotificationStatus::cases(),
        ]);
    }
}
