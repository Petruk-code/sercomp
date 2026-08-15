<?php

namespace App\Http\Controllers;

use App\Support\NotificationFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationsController extends Controller
{
    /**
     * Display the paginated list of the authenticated user's notifications.
     */
    public function index(Request $request): Response
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (DatabaseNotification $notification) => NotificationFormatter::format($notification));

        return Inertia::render('notifications/index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Lightweight JSON payload for the bell badge and popover.
     */
    public function summary(Request $request): JsonResponse
    {
        return response()->json($this->notificationSummary($request));
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, DatabaseNotification $notification): JsonResponse|RedirectResponse
    {
        abort_unless((int) $notification->notifiable_id === $request->user()->id, 403);

        $notification->markAsRead();

        if ($request->expectsJson()) {
            return response()->json($this->notificationSummary($request));
        }

        return back();
    }

    /**
     * Mark every notification of the authenticated user as read.
     */
    public function markAllAsRead(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json($this->notificationSummary($request));
        }

        return back();
    }

    /**
     * Delete a single notification.
     */
    public function destroy(Request $request, DatabaseNotification $notification): JsonResponse|RedirectResponse
    {
        abort_unless((int) $notification->notifiable_id === $request->user()->id, 403);

        $notification->delete();

        if ($request->expectsJson()) {
            return response()->json($this->notificationSummary($request));
        }

        return back();
    }

    /**
     * @return array{unread_count: int, recent: array<int, array<string, string|null>>}
     */
    private function notificationSummary(Request $request): array
    {
        return [
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'recent' => $request->user()
                ->notifications()
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (DatabaseNotification $notification) => NotificationFormatter::format($notification))
                ->values()
                ->toArray(),
        ];
    }
}
