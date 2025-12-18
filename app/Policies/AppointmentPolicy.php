<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->current_organization_id !== $appointment->organization_id) {
            return false;
        }

        $role = $user->getOrganizationRole($user->currentOrganization);

        if (in_array($role, [OrganizationRole::Admin, OrganizationRole::Receptionist, OrganizationRole::Owner], true)) {
            return true;
        }

        if ($role === OrganizationRole::Clinician) {
            return $appointment->user_id === $user->id;
        }

        return false;
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

        return in_array($role, [OrganizationRole::Admin, OrganizationRole::Receptionist, OrganizationRole::Owner], true);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->current_organization_id !== $appointment->organization_id) {
            return false;
        }

        $role = $user->getOrganizationRole($user->currentOrganization);

        if ($role === OrganizationRole::Admin || $role === OrganizationRole::Owner) {
            return true;
        }

        if ($role === OrganizationRole::Clinician) {
            return $appointment->user_id === $user->id;
        }

        return false;
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->current_organization_id !== $appointment->organization_id) {
            return false;
        }

        $role = $user->getOrganizationRole($user->currentOrganization);

        return $role === OrganizationRole::Admin || $role === OrganizationRole::Owner;
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->current_organization_id !== $appointment->organization_id) {
            return false;
        }

        $role = $user->getOrganizationRole($user->currentOrganization);

        if (! in_array($role, [OrganizationRole::Admin, OrganizationRole::Receptionist, OrganizationRole::Owner], true)) {
            return false;
        }

        return $appointment->status->isCancellable();
    }

    public function assignRoom(User $user, Appointment $appointment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->current_organization_id !== $appointment->organization_id) {
            return false;
        }

        $role = $user->getOrganizationRole($user->currentOrganization);

        return in_array($role, [OrganizationRole::Admin, OrganizationRole::Receptionist, OrganizationRole::Owner], true);
    }

    public function restore(User $user, Appointment $appointment): bool
    {
        return false;
    }

    public function forceDelete(User $user, Appointment $appointment): bool
    {
        return false;
    }
}
