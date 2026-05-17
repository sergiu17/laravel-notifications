<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNotificationBatchRequest;
use App\Models\Notification;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class NotificationBatchController extends Controller
{
    public function store(StoreNotificationBatchRequest $request)
    {
        $batchId = strtolower((string) Str::ulid());

        foreach ($request->validated('notifications') as $notification) {
            Notification::query()->create([...$notification, 'batch_id' => $batchId]);
        }

        return response()->json([
            'batch_id' => $batchId,
        ], Response::HTTP_CREATED);
    }
}
