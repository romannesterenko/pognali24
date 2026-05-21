<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\DriverProfileController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\TripBookingController;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
});
Route::prefix('v1')->group(function () {

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::prefix('/trips')->group(function () {
        Route::get('/', [TripController::class, 'index']);
        Route::get('/latest', [TripController::class, 'latest']);
        Route::get('/search', [TripController::class, 'search']);
        Route::get('/{trip}', [TripController::class, 'show']);
    });
    Route::prefix('/users')->group(function () {
        Route::get('/{user}', [UserProfileController::class, 'show']);
    });
    Route::prefix('/cities')->group(function () {
        Route::post('/suggest', [LocationController::class, 'suggest']);
        Route::post('/', [LocationController::class, 'addCity']);
    });
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::prefix('/profile')->group(function () {
            Route::get('/', [ProfileController::class, 'show'])->name('show');
            Route::post('/', [ProfileController::class, 'update'])->name('update');
            Route::post('/avatar', [ProfileController::class, 'avatar'])->name('avatar');
        });
        Route::prefix('/driver-profile')->group(function () {
            Route::post('/', [DriverProfileController::class, 'update']);
            Route::post('/create', [DriverProfileController::class, 'create']);
        });
        Route::prefix('/trips')->group(function () {
            Route::post('/', [TripController::class, 'store']);
            Route::post('/{trip}/book', [TripBookingController::class, 'store']);
            Route::post('/{trip}/start', [TripController::class, 'start']);
            Route::post('/{trip}/complete', [TripController::class, 'complete']);
        });
        Route::prefix('/cars')->group(function () {
            Route::get('/', [CarController::class, 'index']);
            Route::post('/', [CarController::class, 'store']);
            Route::post('/{car}', [CarController::class, 'update']);
            Route::delete('/{car}', [CarController::class, 'destroy']);
        });
        Route::prefix('/driver')->group(function () {
            Route::prefix('/trips')->group(function () {
                Route::get('/', [TripController::class, 'myTrips']);
                Route::post('/', [TripController::class, 'store']);
            });
            Route::get('/bookings', [TripBookingController::class, 'driverBookings']);

        });
        Route::prefix('/bookings')->group(function () {
            Route::get('/', [TripBookingController::class, 'myBookings']);
            Route::post('/{booking}/approve', [TripBookingController::class, 'approve']);
            Route::post('/{booking}/reject', [TripBookingController::class, 'reject']);
            Route::post('/{booking}/cancel', [TripBookingController::class, 'cancel']);
            Route::post('/{booking}/confirm', [TripBookingController::class, 'confirm']);
            Route::post('/{booking}/driver-confirm', [TripBookingController::class, 'driverConfirm']);
            Route::post('/{booking}/reviews', [ReviewController::class, 'store']);
            Route::post('/{booking}/review/passenger', [ReviewController::class, 'store']);
        });
        Route::prefix('/conversations')->group(function () {
            Route::get('/', [ConversationController::class, 'index']);
            Route::get('/unread-count', [ConversationController::class, 'unreadCount']);
            Route::get('/{conversation}', [ConversationController::class, 'show']);
            Route::get('/{conversation}/messages', [MessageController::class, 'index']);
            Route::post('/{conversation}/messages', [MessageController::class, 'store']);
            Route::post('/{conversation}/read', [MessageController::class, 'markAsRead']);
        });
        Route::prefix('notifications')->group(function () {

            Route::get('/', [NotificationController::class, 'index']);

            Route::post('/{notification}/read', [NotificationController::class, 'read']);

            Route::post('/read-all', [NotificationController::class, 'readAll']);

            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        });

        //logout
        Route::post('/logout', [AuthController::class, 'logout']);
    });

});

