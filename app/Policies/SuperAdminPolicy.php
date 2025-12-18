<?php

namespace App\Policies;

use App\Models\User;

class SuperAdminPolicy
{
    public function viewAny(User $user, string $model): bool
    {
        return $user->isSuperAdmin();
    }

    public function viewAnyOrganizations(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function viewAnyUsers(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function manage(User $user, string $model): bool
    {
        return $user->isSuperAdmin();
    }

    public function manageOrganizations(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
