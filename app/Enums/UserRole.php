<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Clinician = 'clinician';
    case Receptionist = 'receptionist';

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }
}
