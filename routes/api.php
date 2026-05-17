<?php

use App\Http\Controllers\NotificationBatchController;
use App\Http\Controllers\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/notifications', [NotificationController::class, 'index']);
Route::post('/notifications', [NotificationController::class, 'store']);
Route::post('/notifications/batch', [NotificationBatchController::class, 'store']);
Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
