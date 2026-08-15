<?php

namespace App\Support;

use Illuminate\Notifications\DatabaseNotification;

final class NotificationFormatter
{
    /**
     * Shape a database notification into a plain client-safe array.
     *
     * @return array{id: string, title: string, message: string, action_url: string|null, read_at: string|null, created_at: string}
     */
    public static function format(DatabaseNotification $notification): array
    {
        return [
            'id' => (string) $notification->id,
            'title' => (string) ($notification->data['title'] ?? 'Notifikasi'),
            'message' => (string) ($notification->data['message'] ?? ''),
            'action_url' => isset($notification->data['action_url']) ? (string) $notification->data['action_url'] : null,
            'read_at' => $notification->read_at?->toISOString(),
            'created_at' => $notification->created_at->toISOString(),
        ];
    }
}
