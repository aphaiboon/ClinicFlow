<?php

namespace App\Policies;

use App\Enums\UserRole;
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
        if ($user->role === UserRole::Admin || $user->role === UserRole::Receptionist) {
            return true;
        }

        if ($user->role === UserRole::Clinician) {
            return $appointment->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Receptionist], true);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        if ($user->role === UserRole::Clinician) {
            return $appointment->user_id === $user->id;
        }

        return false;
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        if (! in_array($user->role, [UserRole::Admin, UserRole::Receptionist], true)) {
            return false;
        }

        return $appointment->status->isCancellable();
    }

    public function assignRoom(User $user, Appointment $appointment): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Receptionist], true);
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
