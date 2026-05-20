<?php

namespace App\Services;

use App\Events\PlatformEvent;
use App\Events\ToastNotification;
use App\Models\Notification;

class NotificationService
{
    public static function send(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $url = null
    ): void {

        $notification = Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'url' => $url,
        ]);

        event(new PlatformEvent(
            userId: $userId,
            type: $type,
            title: $title,
            message: $message,
            url: $url
        ));
    }

    public static function sendToast(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $url = null
    ): void {

        event(new PlatformEvent(
            userId: $userId,
            type: $type,
            title: $title,
            message: $message,
            url: $url
        ));
    }
}
