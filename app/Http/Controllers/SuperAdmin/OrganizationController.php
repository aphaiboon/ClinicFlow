<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function index(): Response
    {
        $organizations = Organization::withCount(['users', 'patients', 'appointments'])
            ->orderBy('name')
            ->paginate(15);

        return Inertia::render('SuperAdmin/OrganizationsList', [
            'organizations' => $organizations,
        ]);
    }

    public function show(Organization $organization): Response
    {
        $organization->loadCount(['users', 'patients', 'appointments', 'examRooms', 'auditLogs']);
        $organization->load(['users']);

        $stats = [
            'patientsCount' => $organization->patients()->count(),
            'appointmentsCount' => $organization->appointments()->count(),
            'examRoomsCount' => $organization->examRooms()->count(),
            'usersCount' => $organization->users()->count(),
        ];

        return Inertia::render('SuperAdmin/OrganizationDataView', [
            'organization' => $organization,
            'stats' => $stats,
        ]);
    }
}
