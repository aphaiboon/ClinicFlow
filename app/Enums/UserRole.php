<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case User = 'user';
    case Admin = 'admin';
    case Clinician = 'clinician';
    case Receptionist = 'receptionist';

    public function isSuperAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }
}
