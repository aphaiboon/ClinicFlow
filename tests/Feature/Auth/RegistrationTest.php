<?php

test('organization registration screen can be rendered', function () {
    $response = $this->get(route('organization.register'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('auth/organization-register')
    );
});

test('guests can register organization and user', function () {
    $response = $this->post(route('organization.register.store'), [
        'name' => 'Test Clinic',
        'user_name' => 'Test User',
        'user_email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});
