<?php

use App\Broadcasting\UserNotificationsChannel;
use App\Events\NotificationPushed;
use App\Listeners\PushDatabaseNotificationToRealtime;
use App\Models\User;
use App\Notifications\LowStockAlert;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

/*
|--------------------------------------------------------------------------
| Realtime: notifikasi database dipush ke websocket (Reverb)
|--------------------------------------------------------------------------
*/

it('broadcasts a realtime event when a database notification is sent', function () {
    Event::fake([NotificationPushed::class]);

    $recipient = User::factory()->create();
    $other = User::factory()->create();

    Notification::send([$recipient, $other], new LowStockAlert(
        collect([]),
    ));

    Event::assertDispatched(NotificationPushed::class, 2);

    Event::assertDispatched(NotificationPushed::class, function (
        NotificationPushed $event,
    ) use ($recipient) {
        if (! $event->user->is($recipient)) {
            return false;
        }

        expect($event->broadcastOn()[0]->name)
            ->toBe('private-App.Models.User.'.$recipient->id);
        expect($event->notificationId)->not->toBeEmpty();
        expect($event->data)->toHaveKey('title');
        expect($event->broadcastAs())->toBe('notification.pushed');

        return true;
    });
});

it('does not push realtime when the notification skips the database channel', function () {
    Event::fake([NotificationPushed::class]);

    $recipient = User::factory()->create();

    // Simulasikan notifikasi lewat channel selain database (mis. mail).
    (new PushDatabaseNotificationToRealtime)
        ->handle(new NotificationSent(
            $recipient,
            new LowStockAlert(collect([])),
            'mail',
        ));

    Event::assertNotDispatched(NotificationPushed::class);
});

it('only allows the owner to subscribe to their notification channel', function () {
    $channel = new UserNotificationsChannel;
    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    expect(($channel)($owner, (string) $owner->id))->toBeTrue();
    expect(($channel)($owner, $owner->id))->toBeTrue();
    expect(($channel)($stranger, (string) $owner->id))->toBeFalse();
});
