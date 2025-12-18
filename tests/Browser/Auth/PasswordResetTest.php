<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

it('can visit the forgot password page', function () {
    $page = visit('/forgot-password');

    $page->assertSee('Forgot password')
        ->assertSee('Email address');
});

it('can request password reset link', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'test@example.com']);

    $page = visit('/forgot-password');

    $page->type('email', 'test@example.com')
        ->press('Email password reset link')
        ->waitForText('Forgot password', 5);

    Notification::assertSentTo($user, ResetPassword::class);
});

it('can reset password with valid token', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('oldpassword123'),
    ]);

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $token = $notification->token;
        $page = visit(route('password.reset', ['token' => $token, 'email' => $user->email]));

        $page->assertSee('Reset password')
            ->type('password', 'newpassword123')
            ->type('password_confirmation', 'newpassword123')
            ->press('Reset password')
            ->assertPathIs('/login');

        $user->refresh();
        expect(Hash::check('newpassword123', $user->password))->toBeTrue();

        return true;
    });
});

it('shows error for invalid reset token', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);

    $page = visit(route('password.reset', ['token' => 'invalid-token', 'email' => $user->email]));

    $page->assertSee('Reset password')
        ->type('password', 'newpassword123')
        ->type('password_confirmation', 'newpassword123')
        ->press('Reset password')
        ->waitForText('email', 5);
});

it('shows success message even for non-existent email', function () {
    Notification::fake();

    $page = visit('/forgot-password');

    $page->type('email', 'nonexistent@example.com')
        ->press('Email password reset link')
        ->waitForText('Forgot password', 5);

    Notification::assertNothingSent();
});
