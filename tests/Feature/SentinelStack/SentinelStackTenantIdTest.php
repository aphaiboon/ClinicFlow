<?php

use App\Enums\OrganizationRole;
use App\Enums\UserRole;
use App\Events\PatientCreated;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Mock HTTP client for SentinelStack
    Http::fake();

    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->organization->id,
    ]);
    $this->organization->users()->attach($this->user->id, [
        'role' => OrganizationRole::Owner->value,
        'joined_at' => now(),
    ]);
});

it('sends organization_id as tenant_id in event envelope', function () {
    $this->actingAs($this->user);

    $patient = Patient::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    Event::dispatch(new PatientCreated($patient));

    Http::assertSent(function ($request) {
        $data = $request->data();

        return isset($data['tenant_id'])
            && $data['tenant_id'] === (string) $this->organization->id;
    });
});

it('uses resource organization_id as tenant_id when available', function () {
    $this->actingAs($this->user);

    $organization2 = Organization::factory()->create();
    $patient = Patient::factory()->create([
        'organization_id' => $organization2->id,
    ]);

    Event::dispatch(new PatientCreated($patient));

    expect($this->capturedEvents)->toHaveCount(1)
        ->and($this->capturedEvents[0]['tenant_id'])->toBe((string) $organization2->id);
});

it('handles null tenant_id when resource has no organization', function () {
    $this->actingAs($this->user);

    // This shouldn't happen in practice due to FK constraints, but test the behavior
    $patient = Patient::factory()->make(['organization_id' => null]);
    // We can't actually create a patient without organization_id due to FK, so skip this test
    $this->markTestSkipped('Cannot create patient without organization_id due to FK constraints');
});
