<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = Conversation::query()
            ->whereHas('members', function ($q) use ($user) {

                $q->where('user_id', $user->id);
            })
            ->with([
                'trip',
                'trip.fromLocality',
                'trip.toLocality',
                'latestMessage.user',
                'members.user.profile',
            ])
            ->latest()
            ->get();

        return response()->json([
            'conversations' => $conversations
        ]);
    }

    public function find(Request $request): JsonResponse
    {
        $conversation = Conversation::query()
            ->whereHas('trip', function ($q) use ($request) {
                $q->where('trip_id', $request->trip_id);
            })
            ->whereHas('members', function ($q) use ($request) {
                $q->where('user_id', $request->driver_id);
            })
            ->whereHas('members', function ($q) use ($request) {
                $q->where('user_id', $request->user_id);
            })
            ->first();

        if (!$conversation) {
            return response()->json([
                'message' => 'Переписка не найдена. Подайте заявку для связи с водителем'
            ], 404);
        }

        return response()->json([
            'conversation' => $conversation
        ]);

        return response()->json([
            'request' => $request->all()
        ]);
    }
    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $exists = $conversation->members()
            ->where('user_id', $request->user()->id)
            ->exists();

        if (!$exists) {
            abort(403);
        }

        $conversation->load([
            'trip.car',
            'trip.fromLocality',
            'trip.toLocality',
            'trip.driver.profile',
            'members.user.profile',
        ]);

        return response()->json([
            'conversation' => $conversation
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $count = Message::query()
            ->whereNull('read_at')
            ->where('user_id', '!=', $userId)
            ->whereHas('conversation.members', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->count();

        return response()->json([
            'count' => $count
        ]);
    }
}
