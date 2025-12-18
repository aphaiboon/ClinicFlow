<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case User = 'user';

    public function isSuperAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }
}
