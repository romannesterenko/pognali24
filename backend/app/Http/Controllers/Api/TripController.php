<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Locality;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'trips' => Trip::with(
                [
                    'user',
                    'car',
                    'driver.receivedReviews',
                    'fromLocality.region',
                    'toLocality.region'
                ])
                ->latest()
                ->get()
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'car_id' => ['required', 'exists:cars,id'],
            'from_fias_id' => [
                'required',
                'exists:localities,fias_id'
            ],

            'to_fias_id' => [
                'required',
                'exists:localities,fias_id'
            ],
            'departure_time' => ['required', 'date'],
            'available_seats' => ['required', 'integer', 'min:1', 'max:8'],
            'price' => ['required', 'numeric', 'min:0'],
            'comment' => ['nullable', 'string'],
        ]);

        $trip = $request->user()->trips()->create($validated);

        return response()->json([
            'success' => true,
            'trip' => $trip
        ]);
    }

    public function show(Trip $trip): JsonResponse
    {
        $trip->load([

            'fromLocality.region',
            'toLocality.region',
            'driver.profile',
            'driver.driverProfile',
            'driver.receivedReviews',
            'car',
        ]);

        return response()->json([
            'trip' => $trip
        ]);
    }

    public function myTrips(Request $request): JsonResponse
    {
        $trips = $request->user()
            ->trips()
            ->with([
                'fromLocality.region',
                'toLocality.region',
                'car',
                'bookings',
                'bookings.passenger',
                'bookings.passenger.profile',
                'bookings.reviews',
                'driver.receivedReviews',
                'bookings.passenger.receivedReviews'
            ])
            ->latest()
            ->get();

        return response()->json([
            'trips' => $trips
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $query = Trip::query()
            ->with([
                'fromLocality.region',
                'toLocality.region',
                'driver.profile',
                'car',
                'driver.receivedReviews'
            ])->where('status', 'active');

        if ($request->from_city) {

            $query->where('from_fias_id', 'like', '%' . $request->from_city . '%');

        }

        if ($request->to_city) {

            $query->where('to_fias_id', 'like', '%' . $request->to_city . '%');
        }

        if ($request->date) {

            $query->whereDate('departure_time', $request->date);
        }

        $trips = $query
            ->latest()
            ->paginate(20);

        return response()->json($trips);
    }
    public function latest(Request $request): JsonResponse
    {
        $trips = Trip::query()
            ->with([
                'driver.profile',
                'car',
                'driver.receivedReviews'
            ])
            ->where('status', 'active')
            ->latest()
            ->limit(4)
            ->get();

        return response()->json($trips);
    }

    public function start(Trip $trip): JsonResponse
    {
        abort_if($trip->user_id !== auth()->id(), 403);

        $trip->update([
            'status' => 'started',
            'started_at' => now(),
        ]);

        return response()->json([
            'success' => true,
        ]);
    }

    public function complete(Trip $trip): JsonResponse
    {
        abort_if($trip->user_id !== auth()->id(), 403);

        $trip->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $trip->bookings()
            ->where('status', 'approved')
            ->update([
                'driver_confirmed_at' => now(),
            ]);

        return response()->json([
            'success' => true,
        ]);
    }
}
