<?php

use Illuminate\Support\Facades\Schedule;

test('the backup, alert, and notification pruning commands are scheduled', function () {
    $commands = collect(Schedule::events())
        ->map(fn ($event) => $event->command)
        ->filter(fn ($command) => is_string($command));

    $scheduled = fn (string $name): bool => $commands->contains(
        fn ($command) => str_contains((string) $command, $name),
    );

    expect($scheduled('app:backup-database'))->toBeTrue()
        ->and($scheduled('app:send-alerts'))->toBeTrue()
        ->and($scheduled('notifications:prune'))->toBeTrue();
});
