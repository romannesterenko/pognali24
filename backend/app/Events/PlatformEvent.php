<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlatformEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public string $type,
        public string $title,
        public string $message,
        public ?string $url = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->userId}")
        ];
    }

    public function broadcastAs(): string
    {
        return 'platform.event';
    }

    public function broadcastWith(): array
    {
        return [
            'userId' => $this->userId,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
