<?php

namespace App\Listeners;

use App\Contracts\HasDatabasePayload;
use App\Events\NotificationPushed;
use App\Models\User;
use Illuminate\Notifications\Events\NotificationSent;

/**
 * Setiap notifikasi yang masuk channel database langsung dipush ke
 * websocket (Reverb) agar lonceng notifikasi update realtime tanpa polling.
 * Listener sinkron — tidak boleh queued (lihat .ai/rules/notifications.md).
 */
class PushDatabaseNotificationToRealtime
{
    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'database') {
            return;
        }

        if (! $event->notifiable instanceof User) {
            return;
        }

        if (! $event->notification instanceof HasDatabasePayload) {
            return;
        }

        broadcast(new NotificationPushed(
            $event->notifiable,
            (string) $event->notification->id,
            $event->notification->toArray($event->notifiable),
        ));
    }
}
