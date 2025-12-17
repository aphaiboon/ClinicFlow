<?php

namespace App\Policies;

use App\Enums\UserRole;
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
        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Receptionist], true);
    }

    public function update(User $user, Patient $patient): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Receptionist], true);
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $user->role === UserRole::Admin;
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
