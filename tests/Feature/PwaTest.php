<?php

test('the service worker is served with PWA headers when built', function () {
    if (! file_exists(public_path('build/sw.js'))) {
        $this->markTestSkipped('Run `npm run build` first to generate the service worker.');
    }

    $this->get(route('pwa.sw'))
        ->assertOk()
        ->assertHeader('Service-Worker-Allowed', '/')
        ->assertHeader('Content-Type', 'application/javascript');
});

test('the service worker route responds 404 before the first build', function () {
    $path = public_path('build/sw.js');

    if (file_exists($path)) {
        $this->markTestSkipped('Service worker already built; nothing to skip-verify.');
    }

    $this->get(route('pwa.sw'))->assertNotFound();
});
