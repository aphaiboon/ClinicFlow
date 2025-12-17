<?php

use App\Models\User;
use Illuminate\Support\Facades\Event;
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
    $response->assertSee('Sign In', false);
});

test('welcome page displays register button when registration is enabled', function () {
    if (! Features::enabled(Features::registration())) {
        $this->markTestSkipped('Registration is not enabled.');
    }

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('Register', false);
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

