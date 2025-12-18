<?php

use App\Models\User;
use Laravel\Fortify\Features;

it('skips two factor tests if feature is disabled', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    expect(true)->toBeTrue();
});

it('redirects to two factor challenge after login with 2FA enabled', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->create([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ]);

    $page = visit('/login');

    $page->type('email', $user->email)
        ->type('password', 'password')
        ->press('Log in')
        ->waitForText('Two Factor', 5);
});
