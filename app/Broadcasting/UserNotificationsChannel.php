<?php

namespace App\Broadcasting;

use App\Models\User;

/**
 * Channel privat notifikasi per-user: App.Models.User.{id}.
 */
class UserNotificationsChannel
{
    /**
     * Hanya pemilik channel yang boleh berlangganan.
     */
    public function __invoke(User $user, string|int $id): bool
    {
        return (int) $user->id === (int) $id;
    }
}
