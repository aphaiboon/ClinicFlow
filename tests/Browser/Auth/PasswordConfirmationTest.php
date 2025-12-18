<?php

use App\Models\User;

it('can visit password confirmation page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit('/user/confirm-password');

    $page->assertSee('Confirm Password')
        ->assertSee('Password');
});

it('can confirm password successfully', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);
    $this->actingAs($user);

    $page = visit('/user/confirm-password');

    $page->type('password', 'password123')
        ->press('Confirm')
        ->waitForText('Dashboard', 5);
});

it('shows error for incorrect password', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);
    $this->actingAs($user);

    $page = visit('/user/confirm-password');

    $page->type('password', 'wrong-password')
        ->press('Confirm')
        ->waitForText('Confirm Password', 5)
        ->assertPathIs('/user/confirm-password');
});
