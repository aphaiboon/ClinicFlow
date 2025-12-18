<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user->role === UserRole::SuperAdmin) {
            return $this->superAdminDashboard();
        }

        return $this->userDashboard($user);
    }

    private function superAdminDashboard(): Response
    {
        $stats = [
            'organizationCount' => Organization::count(),
            'userCount' => User::where('role', UserRole::User)->count(),
            'activeOrganizationCount' => Organization::where('is_active', true)->count(),
        ];

        return Inertia::render('dashboard', [
            'stats' => $stats,
            'role' => 'super_admin',
        ]);
    }

    private function userDashboard(User $user): Response
    {
        $organization = $user->currentOrganization;

        if (! $organization) {
            abort(403, 'No organization assigned to user.');
        }

        // Get stats with eager loading to prevent N+1 queries
        $stats = [
            'patientCount' => $organization->patients()->count(),
            'upcomingAppointmentsCount' => $organization->appointments()
                ->where('appointment_date', '>=', now()->toDateString())
                ->where('status', AppointmentStatus::Scheduled)
                ->count(),
            'activeExamRoomsCount' => $organization->examRooms()
                ->where('is_active', true)
                ->count(),
        ];

        // Get recent appointments with relationships eager loaded
        $recentAppointments = $organization->appointments()
            ->with(['patient', 'user', 'examRoom'])
            ->latest('appointment_date')
            ->latest('appointment_time')
            ->limit(5)
            ->get();

        // Get recent activity with relationships eager loaded
        $recentActivity = $organization->auditLogs()
            ->with('user')
            ->latest('created_at')
            ->limit(5)
            ->get();

        return Inertia::render('dashboard', [
            'stats' => $stats,
            'recentAppointments' => $recentAppointments,
            'recentActivity' => $recentActivity,
            'role' => 'user',
        ]);
    }
}
