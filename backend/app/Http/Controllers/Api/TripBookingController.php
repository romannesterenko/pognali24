<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ConversationMember;
use App\Models\Trip;
use App\Models\TripBooking;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripBookingController extends Controller
{

    public function store(Request $request, Trip $trip): JsonResponse
    {

        if ($trip->driver->id === $request->user()->id) {

            return response()->json([
                'message' => 'Нельзя забронировать свою поездку'
            ], 422);
        }

        if ($trip->available_seats < 1) {

            return response()->json([
                'message' => 'Нет свободных мест'
            ], 422);
        }

        if ($trip->status !== 'active') {

            return response()->json([
                'message' => 'Поездка недоступна'
            ], 422);
        }

        $alreadyExists = $trip->bookings()
            ->where('passenger_id', $request->user()->id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($alreadyExists) {

            return response()->json([
                'message' => 'Вы уже отправили заявку'
            ], 422);

        }

        $trip->bookings()->create([
            'passenger_id' => $request->user()->id,
            'seats' => 1,
            'status' => 'pending',
        ]);

        $conversation = Conversation::create([
            'trip_id' => $trip->id,
        ]);

        ConversationMember::create([
            'conversation_id' => $conversation->id,
            'user_id' => $trip->driver->id,
        ]);

        ConversationMember::create([
            'conversation_id' => $conversation->id,
            'user_id' => $request->user()->id,
        ]);

        NotificationService::send(
            $trip->driver->id,
            'booking_request',
            'Заявка на поездку',
            "Новая заявка на поездку"
        );

        return response()->json([
            'message' => 'Заявка отправлена'
        ]);
    }

    public function driverBookings(Request $request): JsonResponse
    {
        $bookings = TripBooking::query()
            ->whereHas('trip', function ($q) use ($request) {

                $q->where('user_id', $request->user()->id);
            })
            ->with([
                'trip',
                'trip.driver.receivedReviews',
                'passenger.receivedReviews',
                'passenger.profile',
                'reviews'
            ])
            ->latest()
            ->get();

        return response()->json([
            'bookings' => $bookings
        ]);
    }

    public function approve(Request $request, TripBooking $booking): JsonResponse
    {
        if ($booking->trip->driver->id !== $request->user()->id) {
            return response()->json([
                'message' => 'Вы не являетесь создателем этой заявки'
            ], 403);
        }

        if ($booking->status !== 'pending') {

            return response()->json([
                'message' => 'Заявка уже обработана'
            ], 422);
        }

        $trip = $booking->trip;

        if ($trip->available_seats < $booking->seats) {

            return response()->json([
                'message' => 'Недостаточно мест'
            ], 422);
        }

        $booking->update([
            'status' => 'approved'
        ]);

        $trip->decrement('available_seats', $booking->seats);

        $trip->refresh();

        if ($trip->available_seats <= 0) {

            $trip->update([
                'status' => 'full'
            ]);
        }

        NotificationService::send(
            $booking->passenger->id,
            'message',
            'Заявка на поездку принята',
            "Ваша заявка на поездку была одобрена водителем"
        );

        return response()->json([
            'message' => 'Заявка подтверждена'
        ]);
    }

    public function reject(Request $request, TripBooking $booking): JsonResponse
    {
        if ($booking->trip->driver->id !== $request->user()->id) {
            return response()->json([
                'message' => 'Вы не являетесь создателем этой заявки'
            ], 403);
        }

        if ($booking->status !== 'pending') {

            return response()->json([
                'message' => 'Заявка уже обработана'
            ], 422);
        }

        $booking->update([
            'status' => 'rejected'
        ]);

        NotificationService::send(
            $booking->passenger->id,
            'cancel_request',
            'Заявка на поездку отклонена',
            "Ваша заявка на поездку была отклонена водителем"
        );

        return response()->json([
            'message' => 'Заявка отклонена'
        ]);
    }

    public function myBookings(Request $request): JsonResponse
    {
        $bookings = $request->user()
            ->bookings()
            ->with([
                'trip.car',
                'trip.driver.profile',
                'trip.fromLocality.region',
                'trip.toLocality.region',
                'reviews',
                'trip.driver.receivedReviews',
                'passenger.receivedReviews',
            ])
            ->latest()
            ->get();

        return response()->json([
            'bookings' => $bookings
        ]);
    }

    public function cancel(Request $request, TripBooking $booking): JsonResponse
    {
        $user = $request->user();

        $trip = $booking->trip;

        $isPassenger = $booking->passenger_id === $user->id;

        $isDriver = $trip->driver->id === $user->id;

        if (!$isPassenger && !$isDriver) {

            abort(403);
        }

        if (!in_array($booking->status, ['pending', 'approved'])) {

            return response()->json([
                'message' => 'Нельзя отменить заявку'
            ], 422);
        }

        if ($booking->status === 'approved') {

            $trip->increment('available_seats', $booking->seats);

            if ($trip->status === 'full') {

                $trip->update([
                    'status' => 'active'
                ]);
            }
        }

        $booking->update([
            'status' => 'cancelled'
        ]);

        return response()->json([
            'message' => 'Заявка отменена'
        ]);
    }

    public function confirm(TripBooking $booking): JsonResponse
    {
        abort_if($booking->passenger_id !== auth()->id(), 403);

        $booking->update([
            'passenger_confirmed_at' => now(),
        ]);

        if ($booking->trip->status !== 'completed') {

            return response()->json([
                'message' => 'Поездка еще не завершена водителем'
            ], 422);
        }

        $booking->update([
            'passenger_confirmed_at' => now(),
        ]);

        $booking->refresh();

        if (
            $booking->passenger_confirmed_at &&
            $booking->driver_confirmed_at
        ) {

            $booking->update([
                'status' => 'completed',
            ]);
            NotificationService::send(
                $booking->trip->driver->id,
                'message',
                'Поездка одобрена',
                "Поездка была закрыта пассажиром"
            );
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function driverConfirm(TripBooking $booking): JsonResponse
    {
        abort_if(
            $booking->trip->user->id !== auth()->id(),
            403
        );

        $booking->update([
            'driver_confirmed_at' => now(),
        ]);

        NotificationService::send(
            $booking->passenger->id,
            'approve_request',
            'Заявка на поездку одобрена',
            "Ваша заявка на поездку была одобрена водителем"
        );

        if (
            $booking->passenger_confirmed_at &&
            $booking->driver_confirmed_at
        ) {

            $booking->update([
                'status' => 'completed',
            ]);
        }

        return response()->json([
            'success' => true,
        ]);
    }

}
