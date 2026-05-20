<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverProfileController extends Controller
{
    public function create(Request $request): JsonResponse
    {
        $existing = $request->user()->driverProfile;

        if ($existing) {

            return response()->json([
                'success' => true,
                'driver_profile' => $existing,
            ]);
        }

        $profile = $request->user()->driverProfile()->create([
            'about' => null,
            'experience' => null,
        ]);

        return response()->json([
            'success' => true,
            'driver_profile' => $profile,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'about' => ['nullable', 'string', 'max:1000'],
            'experience' => ['nullable', 'integer', 'min:0', 'max:80'],
        ]);

        $driverProfile = $request->user()->driverProfile;

        if (!$driverProfile) {

            return response()->json([
                'message' => 'Driver profile not found',
            ], 404);
        }

        $driverProfile->update($validated);

        return response()->json([
            'success' => true,
            'driver_profile' => $driverProfile->fresh(),
        ]);
    }

}
