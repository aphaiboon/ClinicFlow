<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class SuperAdminController extends Controller
{
    public function dashboard(): Response
    {
        $stats = [
            'organizationCount' => Organization::count(),
            'userCount' => User::where('role', UserRole::User)->count(),
            'activeOrganizationCount' => Organization::where('is_active', true)->count(),
        ];

        return Inertia::render('SuperAdmin/Dashboard', [
            'stats' => $stats,
        ]);
    }
}
