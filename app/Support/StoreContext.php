<?php

namespace App\Support;

use App\Models\Store;
use App\Models\User;
use Illuminate\Contracts\Container\Container;

/**
 * Resolusi "cabang aktif" untuk sesi kerja saat ini:
 * 1. User terikat cabang (users.store_id terisi) → selalu cabang itu.
 * 2. Owner/admin bebas → pakai pilihan di session (active_store_id).
 * 3. Belum ada konteks (console, publik, belum memilih) → null = tanpa scoping.
 *
 * Memo disimpan di container (bukan static) agar ikut ter-reset
 * per request/per test dan aman untuk long-running worker (Octane).
 */
class StoreContext
{
    private const string CACHE_KEY = 'store_context.memo';

    public static function id(): ?int
    {
        $container = self::container();

        if ($container->bound(self::CACHE_KEY)) {
            return $container->make(self::CACHE_KEY);
        }

        $id = self::resolve();

        $container->instance(self::CACHE_KEY, $id);

        return $id;
    }

    /**
     * Cabang aktif sebagai model, atau null bila tanpa konteks.
     */
    public static function store(): ?Store
    {
        $id = self::id();

        return $id === null ? null : Store::query()->find($id);
    }

    /**
     * Apakah user ini boleh memindah cabang aktif (tidak terikat satu cabang).
     */
    public static function canSwitch(User $user): bool
    {
        return $user->store_id === null;
    }

    public static function set(?int $storeId): void
    {
        if ($storeId === null) {
            session()->forget('active_store_id');
        } else {
            session(['active_store_id' => $storeId]);
        }

        self::flush();
    }

    /**
     * Hapus memo (wajib setelah session/identitas berubah dalam request yang sama).
     */
    public static function flush(): void
    {
        self::container()->forgetInstance(self::CACHE_KEY);
    }

    private static function resolve(): ?int
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        if ($user->store_id) {
            return (int) $user->store_id;
        }

        $active = session('active_store_id');

        if (is_numeric($active) && Store::query()->whereKey((int) $active)->exists()) {
            return (int) $active;
        }

        return null;
    }

    private static function container(): Container
    {
        return app();
    }
}
