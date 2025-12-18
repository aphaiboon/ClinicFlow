<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PatientDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $patient = Auth::guard('patient')->user();

        $upcomingAppointments = $patient->appointments()
            ->where('appointment_date', '>=', now()->toDateString())
            ->where('status', '!=', 'cancelled')
            ->with(['user', 'examRoom'])
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->limit(5)
            ->get();

        $recentAppointments = $patient->appointments()
            ->where('appointment_date', '<', now()->toDateString())
            ->with(['user', 'examRoom'])
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('Patient/Dashboard', [
            'upcomingAppointments' => $upcomingAppointments,
            'recentAppointments' => $recentAppointments,
        ]);
    }
}
