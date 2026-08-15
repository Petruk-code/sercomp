<?php

use App\Support\WhatsApp;

it('normalizes Indonesian phone numbers to the international format', function (string $input, string $expected) {
    expect(WhatsApp::normalize($input))->toBe($expected);
})->with([
    ['081234567890', '6281234567890'],
    ['+62 812-3456-7890', '6281234567890'],
    ['6281234567890', '6281234567890'],
    ['81234567890', '6281234567890'],
    ['(0812) 3456-7890', '6281234567890'],
    ['', ''],
    ['abc', ''],
]);

it('builds a wa.me link with a url-encoded message', function () {
    $url = WhatsApp::link('081234567890', 'Halo Budi, cek status servis di: http://localhost/tracking?no=SRV-1');

    expect($url)->toBe('https://wa.me/6281234567890?text='.rawurlencode('Halo Budi, cek status servis di: http://localhost/tracking?no=SRV-1'))
        ->and($url)->toStartWith('https://wa.me/6281234567890?text=');
});

it('builds a tracking message containing the invoice and url', function () {
    $message = WhatsApp::trackingMessage('Budi', 'SRV-20260812-0001', 'http://localhost/tracking?no=SRV-20260812-0001');

    expect($message)->toContain('Budi')
        ->and($message)->toContain('SRV-20260812-0001')
        ->and($message)->toContain('http://localhost/tracking?no=SRV-20260812-0001');
});

it('omits the greeting when the customer has no name', function () {
    $message = WhatsApp::trackingMessage('', 'SRV-1', 'http://localhost/tracking?no=SRV-1');

    expect($message)->not->toContain('Halo')
        ->and($message)->toContain('SRV-1');
});
