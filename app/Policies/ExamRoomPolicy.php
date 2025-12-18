<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\ExamRoom;
use App\Models\User;

class ExamRoomPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ExamRoom $examRoom): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->current_organization_id === $examRoom->organization_id;
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->current_organization_id) {
            return false;
        }

        $role = $user->getOrganizationRole($user->currentOrganization);

        return $role === OrganizationRole::Admin || $role === OrganizationRole::Owner;
    }

    public function update(User $user, ExamRoom $examRoom): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->current_organization_id !== $examRoom->organization_id) {
            return false;
        }

        $role = $user->getOrganizationRole($user->currentOrganization);

        return $role === OrganizationRole::Admin || $role === OrganizationRole::Owner;
    }

    public function delete(User $user, ExamRoom $examRoom): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->current_organization_id !== $examRoom->organization_id) {
            return false;
        }

        $role = $user->getOrganizationRole($user->currentOrganization);

        return $role === OrganizationRole::Admin || $role === OrganizationRole::Owner;
    }

    public function activate(User $user, ExamRoom $examRoom): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->current_organization_id !== $examRoom->organization_id) {
            return false;
        }

        $role = $user->getOrganizationRole($user->currentOrganization);

        return $role === OrganizationRole::Admin || $role === OrganizationRole::Owner;
    }

    public function deactivate(User $user, ExamRoom $examRoom): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->current_organization_id !== $examRoom->organization_id) {
            return false;
        }

        $role = $user->getOrganizationRole($user->currentOrganization);

        return $role === OrganizationRole::Admin || $role === OrganizationRole::Owner;
    }

    public function restore(User $user, ExamRoom $examRoom): bool
    {
        return false;
    }

    public function forceDelete(User $user, ExamRoom $examRoom): bool
    {
        return false;
    }
}
