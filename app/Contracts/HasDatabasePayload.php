<?php

namespace App\Contracts;

/**
 * Notifikasi dengan payload database — wajib untuk ikut dipush realtime
 * (lihat PushDatabaseNotificationToRealtime).
 */
interface HasDatabasePayload
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array;
}
