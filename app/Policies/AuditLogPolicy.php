<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
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

    public function view(User $user, AuditLog $auditLog): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->current_organization_id !== $auditLog->organization_id) {
            return false;
        }

        $role = $user->getOrganizationRole($user->currentOrganization);

        return $role === OrganizationRole::Admin || $role === OrganizationRole::Owner;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    public function delete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    public function restore(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    public function forceDelete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }
}
