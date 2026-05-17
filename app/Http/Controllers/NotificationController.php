<?php

namespace App\Http\Controllers;

use App\Enums\NotificationStatus;
use App\Http\Requests\NotificationRequest;
use App\Http\Requests\StoreNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Exception;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(NotificationRequest $request)
    {
        $notifications = Notification::filter($request->validated())
            ->cursorPaginate(25);

        return NotificationResource::collection($notifications);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreNotificationRequest $request)
    {
        $notification = Notification::query()->create($request->validated());

        return NotificationResource::make($notification);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws Exception
     */
    public function destroy(Notification $notification)
    {
        abort_if(
            $notification->status !== NotificationStatus::Pending,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Notification cannot be canceled.'
        );

        $notification->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
