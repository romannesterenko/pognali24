<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripBooking;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserProfileController extends Controller
{
    public function show(User $user): JsonResponse
    {
        $user->load([
            'profile',
            'cars',
            'driverProfile'
        ]);

        $reviews = $user->receivedReviews()
            ->with('fromUser')
            ->latest()
            ->get();

        $driverReviews = $user->receivedReviews()
            ->where('type', 'passenger_to_driver')
            ->get();

        $passengerReviews = $user->receivedReviews()
            ->where('type', 'driver_to_passenger')
            ->get();

        $driverRating = $driverReviews->avg('rating');

        $passengerRating = $passengerReviews->avg('rating');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->profile?->avatar_url,
                'created_at' => $user->created_at,
                'ratings' => [
                    'driver' => $driverRating
                        ? round($driverRating, 1)
                        : null,

                    'passenger' => $passengerRating
                        ? round($passengerRating, 1)
                        : null,
                ],
                'rating' => round(
                    $reviews->avg('rating') ?? 0,
                    1
                ),
                'stats' => [

                    'driver_trips' => Trip::query()
                        ->where('user_id', $user->id)
                        ->count(),

                    'completed_driver_trips' => Trip::query()
                        ->where('user_id', $user->id)
                        ->where('status', 'completed')
                        ->count(),

                    'passenger_trips' => TripBooking::query()
                        ->where('passenger_id', $user->id)
                        ->where('status', 'completed')
                        ->count(),
                ],
                'reviews_count' => $reviews->count(),
                'reviews' => $reviews,
                'has_driver_profile' => !is_null($user->driverProfile),
            ],
        ]);
    }
}
