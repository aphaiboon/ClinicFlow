<?php

use App\Enums\OrganizationRole;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create existing users without organization memberships
    $this->user1 = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => null]);
    $this->user2 = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => null]);
    $this->user3 = User::factory()->create(['role' => UserRole::SuperAdmin, 'current_organization_id' => null]);
});

it('creates default organization during migration if it does not exist', function () {
    Organization::where('name', 'Default Clinic')->delete();

    Artisan::call('organizations:migrate-existing-data');

    $organization = Organization::where('name', 'Default Clinic')->first();
    expect($organization)->not->toBeNull()
        ->and($organization->is_active)->toBeTrue();
});

it('uses existing default organization if it already exists', function () {
    $existingOrg = Organization::factory()->create(['name' => 'Default Clinic', 'slug' => 'default-clinic']);

    Artisan::call('organizations:migrate-existing-data');

    $organizations = Organization::where('name', 'Default Clinic')->count();
    expect($organizations)->toBe(1)
        ->and(Organization::where('name', 'Default Clinic')->first()->id)->toBe($existingOrg->id);
});

it('attaches existing users to default organization as owners', function () {
    Artisan::call('organizations:migrate-existing-data');

    $organization = Organization::where('name', 'Default Clinic')->first();
    $this->user1->refresh();
    $this->user2->refresh();

    expect($this->user1->organizations()->where('organization_id', $organization->id)->exists())->toBeTrue()
        ->and($this->user2->organizations()->where('organization_id', $organization->id)->exists())->toBeTrue()
        ->and($this->user1->organizations()->where('organization_id', $organization->id)->first()->pivot->role)->toBe(OrganizationRole::Owner->value)
        ->and($this->user2->organizations()->where('organization_id', $organization->id)->first()->pivot->role)->toBe(OrganizationRole::Owner->value);
});

it('does not attach super admin to default organization', function () {
    Artisan::call('organizations:migrate-existing-data');

    $organization = Organization::where('name', 'Default Clinic')->first();
    $this->user3->refresh();

    expect($this->user3->organizations()->where('organization_id', $organization->id)->exists())->toBeFalse();
});

it('sets current_organization_id for regular users', function () {
    Artisan::call('organizations:migrate-existing-data');

    $organization = Organization::where('name', 'Default Clinic')->first();
    $this->user1->refresh();
    $this->user2->refresh();

    expect($this->user1->current_organization_id)->toBe($organization->id)
        ->and($this->user2->current_organization_id)->toBe($organization->id);
});

it('does not set current_organization_id for super admin', function () {
    Artisan::call('organizations:migrate-existing-data');

    $this->user3->refresh();

    expect($this->user3->current_organization_id)->toBeNull();
});

it('does not duplicate user attachments when run multiple times', function () {
    Artisan::call('organizations:migrate-existing-data');
    Artisan::call('organizations:migrate-existing-data');

    $organization = Organization::where('name', 'Default Clinic')->first();
    $this->user1->refresh();

    $attachments = $this->user1->organizations()->where('organization_id', $organization->id)->count();
    expect($attachments)->toBe(1);
});

it('does not overwrite existing current_organization_id', function () {
    $existingOrg = Organization::factory()->create();
    $this->user1->update(['current_organization_id' => $existingOrg->id]);

    Artisan::call('organizations:migrate-existing-data');

    $this->user1->refresh();
    expect($this->user1->current_organization_id)->toBe($existingOrg->id);
});

it('is idempotent and does not create duplicate organizations', function () {
    Artisan::call('organizations:migrate-existing-data');
    Artisan::call('organizations:migrate-existing-data');

    $organizations = Organization::where('name', 'Default Clinic')->count();
    expect($organizations)->toBe(1);
});
