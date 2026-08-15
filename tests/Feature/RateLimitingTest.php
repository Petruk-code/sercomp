<?php

use App\Models\ServiceOrder;
use App\Models\User;

test('public tracking lookups are rate limited per IP', function () {
    foreach (range(1, 20) as $i) {
        $this->get(route('tracking'))->assertOk();
    }

    $this->get(route('tracking'))->assertStatus(429);
});

test('different IPs are rate limited independently', function () {
    $this->get(route('tracking'))->assertOk();

    $this->withHeaders(['X-Forwarded-For' => '10.0.0.2'])
        ->get(route('tracking'))
        ->assertOk();
});

test('pdf download endpoints are rate limited per user', function () {
    $user = User::factory()->create();
    $service = ServiceOrder::factory()->create();

    $this->actingAs($user);

    foreach (range(1, 30) as $i) {
        $this->get(route('services.print', $service));
    }

    $this->get(route('services.print', $service))->assertStatus(429);
});
