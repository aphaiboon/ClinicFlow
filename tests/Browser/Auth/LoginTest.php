<?php

use App\Models\User;

it('can visit the login page', function () {
    $page = visit('/login');

    $page->assertSee('Log in to your account')
        ->assertSee('Email address')
        ->assertSee('Password');
});

it('can log in with valid credentials', function () {
    $user = User::factory()->withoutTwoFactor()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $page = visit('/login');

    $page->type('email', 'test@example.com')
        ->type('password', 'password123')
        ->press('Log in')
        ->assertPathIs('/dashboard');

    $this->assertAuthenticatedAs($user);
});

it('shows error for invalid credentials', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $page = visit('/login');

    $page->type('email', 'test@example.com')
        ->type('password', 'wrong-password')
        ->press('Log in')
        ->assertSee('These credentials do not match our records');

    $this->assertGuest();
});

it('shows error for non-existent user', function () {
    $page = visit('/login');

    $page->type('email', 'nonexistent@example.com')
        ->type('password', 'password123')
        ->press('Log in')
        ->assertSee('These credentials do not match our records');

    $this->assertGuest();
});

it('can navigate to registration page from login', function () {
    $page = visit('/login');

    $page->click('Sign up')
        ->assertPathIs('/register');
});

it('can navigate to forgot password page', function () {
    $page = visit('/login');

    $page->click('Forgot password?')
        ->assertPathIs('/forgot-password');
});

it('can use remember me checkbox', function () {
    $user = User::factory()->withoutTwoFactor()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $page = visit('/login');

    $page->type('email', 'test@example.com')
        ->type('password', 'password123')
        ->check('remember')
        ->press('Log in')
        ->assertPathIs('/dashboard');

    $this->assertAuthenticatedAs($user);
});
