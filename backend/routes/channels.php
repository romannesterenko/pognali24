<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(
    'conversation.{conversationId}',
    function ($user, $conversationId) {

        return Conversation::where('id', $conversationId)
            ->whereHas('members', function ($q) use ($user) {

                $q->where('user_id', $user->id);
            })
            ->exists();
    }
);

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
