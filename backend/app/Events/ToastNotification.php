<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ToastNotification implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $userId,
        public string $type,
        public string $title,
        public string $message,
        public ?array $data = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'user.' . $this->userId
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'toast.notification';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }
}
