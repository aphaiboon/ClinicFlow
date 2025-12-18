<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Clinician = 'clinician';
    case Receptionist = 'receptionist';

    public function isOwner(): bool
    {
        return $this === self::Owner;
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function canManageMembers(): bool
    {
        return $this === self::Owner || $this === self::Admin;
    }

    public function canManageOrganization(): bool
    {
        return $this === self::Owner || $this === self::Admin;
    }
}
