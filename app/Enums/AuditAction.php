<?php

namespace App\Enums;

enum AuditAction: string
{
    case Create = 'create';
    case Read = 'read';
    case Update = 'update';
    case Delete = 'delete';
    case Login = 'login';
    case Logout = 'logout';
}
