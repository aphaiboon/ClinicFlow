<?php

namespace App\Policies;

use App\Enums\UserRole;
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
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, ExamRoom $examRoom): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, ExamRoom $examRoom): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function activate(User $user, ExamRoom $examRoom): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function deactivate(User $user, ExamRoom $examRoom): bool
    {
        return $user->role === UserRole::Admin;
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
