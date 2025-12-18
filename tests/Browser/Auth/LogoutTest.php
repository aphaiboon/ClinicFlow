<?php

use App\Models\User;

it('redirects authenticated user accessing protected route after logout', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post(route('logout'));

    $this->assertGuest();

    $page = visit('/dashboard');

    $page->waitForText('Sign In', 5)
        ->assertPathIs('/login');
});
