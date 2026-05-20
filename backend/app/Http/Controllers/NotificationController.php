<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return response()->json($notifications);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user()
                ->notifications()
                ->whereNull('read_at')
                ->count()
        ]);
    }

    public function read(
        Request $request,
        Notification $notification
    ): JsonResponse
    {
        abort_if(
            $notification->user_id !== $request->user()->id,
            403
        );

        $notification->update([
            'read_at' => now()
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()
            ->notifications()
            ->whereNull('read_at')
            ->update([
                'read_at' => now()
            ]);

        return response()->json([
            'success' => true
        ]);
    }
}
