---
paths:
  - 'tests/**'
---

# Tests

## Testing broadcast: use Event::fake, NOT Broadcast::fake
phpunit.xml sets BROADCAST_CONNECTION=null, so tests NEVER need a running Reverb server. Trap: `Broadcast::fake()` throws "Call to undefined method NullBroadcaster::fake()" with the null driver — do NOT use it. To assert realtime pushes, use a partial event fake instead: `Event::fake([NotificationPushed::class]);` then send the notification and assert with `Event::assertDispatched(NotificationPushed::class, ...)`. Note `PrivateChannel->name` already includes the `private-` prefix in Laravel 13 (expect 'private-App.Models.User.{id}'). Also: NotificationSent::$channel is a STRING ('database'), not an array.
