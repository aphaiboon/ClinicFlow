<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

it('can visit email verification notice page', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user);

    $page = visit('/email/verify');

    $page->assertSee('Verify Email');
});

it('can verify email with valid verification link', function () {
    Event::fake();

    $user = User::factory()->unverified()->create();
    $this->actingAs($user);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $page = visit($verificationUrl);

    $page->waitForText('Dashboard', 5);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('redirects verified user away from verification notice', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($user);

    $page = visit('/email/verify');

    $page->waitForText('Dashboard', 5);
});
