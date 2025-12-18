<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->current_organization_id === $patient->organization_id;
    }

    /**
     * Determine if a patient can view their own profile.
     */
    public function patientView(\App\Models\Patient $patient, Patient $model): bool
    {
        return $patient->id === $model->id;
    }

    /**
     * Determine if a patient can update their own profile.
     */
    public function patientUpdate(\App\Models\Patient $patient, Patient $model): bool
    {
        return $patient->id === $model->id;
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

        return in_array($role, [OrganizationRole::Admin, OrganizationRole::Receptionist], true);
    }

    public function update(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->current_organization_id !== $patient->organization_id) {
            return false;
        }

        $role = $user->getOrganizationRole($user->currentOrganization);

        return in_array($role, [OrganizationRole::Admin, OrganizationRole::Receptionist], true);
    }

    public function delete(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->current_organization_id !== $patient->organization_id) {
            return false;
        }

        $role = $user->getOrganizationRole($user->currentOrganization);

        return $role === OrganizationRole::Admin || $role === OrganizationRole::Owner;
    }

    public function restore(User $user, Patient $patient): bool
    {
        return false;
    }

    public function forceDelete(User $user, Patient $patient): bool
    {
        return false;
    }
}
