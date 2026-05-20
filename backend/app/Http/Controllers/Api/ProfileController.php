<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->load([
                'profile',
                'driverProfile',
                'cars',
            ])
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'string'],
            'about' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
        ]);

        $profile = $request->user()->profile;

        $profile->update($validated);

        return response()->json([
            'success' => true,
            'profile' => $profile->fresh(),
        ]);
    }

    public function avatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048'],
        ]);

        $profile = $request->user()->profile;

        if ($profile->avatar) {

            Storage::disk('public')->delete($profile->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');

        $profile->update([
            'avatar' => $path,
        ]);

        return response()->json([
            'success' => true,
            'avatar_url' => $profile->fresh()->avatar_url,
        ]);
    }}
