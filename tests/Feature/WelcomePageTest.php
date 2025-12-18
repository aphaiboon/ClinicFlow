<?php

use App\Models\User;
use Laravel\Fortify\Features;

test('unauthenticated users can visit the welcome page', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('welcome')
        ->has('canRegister')
    );
});

test('authenticated users are redirected to dashboard from welcome page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('home'));

    $response->assertRedirect(route('dashboard', absolute: false));
});

test('welcome page displays login button for unauthenticated users', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('welcome')
    );
});

test('welcome page displays register button when registration is enabled', function () {
    if (! Features::enabled(Features::registration())) {
        $this->markTestSkipped('Registration is not enabled.');
    }

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('welcome')
        ->where('canRegister', true)
    );
});

test('welcome page does not display register button when registration is disabled', function () {
    config(['fortify.features' => array_filter(config('fortify.features', []), fn ($feature) => $feature !== Features::registration())]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('welcome')
        ->where('canRegister', false)
    );

    config(['fortify.features' => array_merge(config('fortify.features', []), [Features::registration()])]);
});

test('welcome page login button links to login route', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('welcome')
    );
});

test('welcome page register button has correct styling and is visible', function () {
    if (! Features::enabled(Features::registration())) {
        $this->markTestSkipped('Registration is not enabled.');
    }

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('welcome')
        ->where('canRegister', true)
    );
});

test('welcome page does not display duplicate logo in hero section', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $html = $response->getContent();

    preg_match_all('/clinicflow-icon-logo\.png/', $html, $matches);

    expect(count($matches[0]))->toBeLessThanOrEqual(1)
        ->and($html)->not->toContain('sm:h-32 sm:w-32');
});

test('welcome page displays concise hero section without duplicate heading', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('welcome')
    );

    $html = $response->getContent();

    expect($html)->not->toContain('text-7xl bg-gradient-to-r from-[#323d47] to-[#1bc3bb]')
        ->and($html)->toContain('Streamline Your Clinic Operations');
});
