<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganizationService
{
    public function create(array $data, User $owner): Organization
    {
        return DB::transaction(function () use ($data, $owner) {
            if (! isset($data['slug'])) {
                $baseSlug = Str::slug($data['name']);
                $slug = $baseSlug;
                $counter = 1;

                while (Organization::where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.$counter;
                    $counter++;
                }

                $data['slug'] = $slug;
            }

            $organization = Organization::create($data);

            $organization->users()->attach($owner->id, [
                'role' => OrganizationRole::Owner->value,
                'joined_at' => now(),
            ]);

            return $organization->fresh();
        });
    }

    public function addMember(Organization $organization, User $user, string $role): void
    {
        if ($organization->users()->where('user_id', $user->id)->exists()) {
            throw new \RuntimeException('User is already a member of this organization.');
        }

        $organization->users()->attach($user->id, [
            'role' => $role,
            'joined_at' => now(),
        ]);
    }

    public function removeMember(Organization $organization, User $user): void
    {
        $pivot = $organization->users()->where('user_id', $user->id)->first()?->pivot;

        if (! $pivot) {
            throw new \RuntimeException('User is not a member of this organization.');
        }

        if ($pivot->role === OrganizationRole::Owner->value) {
            $ownerCount = $organization->users()
                ->wherePivot('role', OrganizationRole::Owner->value)
                ->count();

            if ($ownerCount <= 1) {
                throw new \RuntimeException('Cannot remove the last owner');
            }
        }

        $organization->users()->detach($user->id);
    }

    public function updateMemberRole(Organization $organization, User $user, string $role): void
    {
        if (! $organization->users()->where('user_id', $user->id)->exists()) {
            throw new \RuntimeException('User is not a member of this organization.');
        }

        $organization->users()->updateExistingPivot($user->id, ['role' => $role]);
    }

    public function switchUserOrganization(User $user, Organization $organization): void
    {
        if (! $user->organizations()->where('organizations.id', $organization->id)->exists()) {
            throw new \RuntimeException('User is not a member of this organization.');
        }

        $user->update(['current_organization_id' => $organization->id]);
    }
}
