<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Car;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'cars' => $request->user()->cars
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1950', 'max:' . date('Y')],
            'plate_number' => ['nullable', 'string', 'max:50'],
            'seats' => ['required', 'integer', 'min:1', 'max:8'],
        ]);

        $car = $request->user()->cars()->create($validated);

        return response()->json([
            'success' => true,
            'car' => $car
        ]);
    }

    public function update(Request $request, Car $car): JsonResponse
    {
        if ($car->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1950', 'max:' . date('Y')],
            'plate_number' => ['nullable', 'string', 'max:50'],
            'seats' => ['required', 'integer', 'min:1', 'max:8'],
        ]);

        $car->update($validated);

        return response()->json([
            'success' => true,
            'car' => $car->fresh()
        ]);
    }

    public function destroy(Request $request, Car $car): JsonResponse
    {
        if ($car->user_id !== $request->user()->id) {
            abort(403);
        }

        $car->delete();

        return response()->json([
            'success' => true
        ]);
    }
}
