<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Events\PlatformEvent;
use App\Events\ToastNotification;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $exists = $conversation->members()
            ->where('user_id', $request->user()->id)
            ->exists();

        if (!$exists) {
            abort(403);
        }

        return response()->json([
            'messages' => $conversation->messages()
                ->with('user')
                ->latest()
                ->get()
                ->reverse()
                ->values()
        ]);
    }

    public function store(
        Request $request,
        Conversation $conversation
    ): JsonResponse
    {
        $exists = $conversation->members()
            ->where('user_id', $request->user()->id)
            ->exists();

        if (!$exists) {
            abort(403);
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $message = $conversation->messages()->create([
            'user_id' => $request->user()->id,
            'message' => $data['message'],
        ]);

        broadcast(new MessageSent($message))->toOthers();

        // Все участники кроме отправителя
        $receiverIds = $conversation->members()
            ->where('user_id', '!=', $request->user()->id)
            ->pluck('user_id');

        foreach ($receiverIds as $receiverId) {
            NotificationService::send(
                $receiverId,
                'message',
                'Новое сообщение',
                $request->user()->name . ': ' . $data['message']
            );
        }

        return response()->json([
            'message' => $message->load('user')
        ]);
    }

    public function markAsRead(Conversation $conversation, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        Message::where('conversation_id', $conversation->id)
            ->where('user_id', '!=', $userId)
            ->whereNull('read_at')
            ->update([
                'read_at' => now()
            ]);

        return response()->json(['status' => 'ok']);
    }
}
