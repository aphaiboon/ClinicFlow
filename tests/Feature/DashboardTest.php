<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $organization = \App\Models\Organization::factory()->create();
    $user = User::factory()->create([
        'current_organization_id' => $organization->id,
    ]);
    $organization->users()->attach($user->id, [
        'role' => \App\Enums\OrganizationRole::Admin->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($user);

    $this->get(route('dashboard'))->assertOk();
});
