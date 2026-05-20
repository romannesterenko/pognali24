<?php

namespace App\Http\Controllers\Api;

use App\Events\ToastNotification;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Review;
use App\Models\TripBooking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, TripBooking $booking): JsonResponse
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        abort_if(
            $booking->status !== 'completed',
            422,
            'Поездка не завершена'
        );

        $user = auth()->user();

        // пассажир -> водитель
        if ($booking->passenger_id === $user->id) {

            $revieweeId = $booking->trip->user->id;

            $type = 'passenger_to_driver';

        }
        // водитель -> пассажир

        elseif ($booking->trip->user->id === $user->id) {

            $revieweeId = $booking->passenger_id;

            $type = 'driver_to_passenger';

        } else {

            abort(403);
        }

        // уже оставлял отзыв

        $exists = Review::where('trip_booking_id', $booking->id)
            ->where('reviewer_id', $user->id)
            ->exists();

        if ($exists) {

            return response()->json([
                'message' => 'Отзыв уже оставлен'
            ], 422);
        }

        Review::create([
            'trip_booking_id' => $booking->id,
            'reviewer_id' => $user->id,
            'reviewee_id' => $revieweeId,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'type' => $type,
        ]);

        event(new ToastNotification(
            userId: $revieweeId,

            type: 'review',

            title: 'Новый отзыв',

            message: 'Вам оставили отзыв',
        ));

        return response()->json([
            'success' => true,
        ]);
    }
}
