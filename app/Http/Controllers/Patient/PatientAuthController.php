<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Services\PatientAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PatientAuthController extends Controller
{
    public function __construct(
        protected PatientAuthService $authService
    ) {}

    public function showLoginForm(Request $request): Response
    {
        return Inertia::render('Patient/Auth/Login', [
            'prefilledEmail' => $request->query('email'),
        ]);
    }

    public function requestMagicLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $this->authService->sendMagicLink($request->email);

            return back()->with('status', 'We have sent you a magic link! Please check your email.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }

    public function verifyMagicLink(Request $request, string $token): RedirectResponse
    {
        $patient = $this->authService->verifyMagicLink($token);

        if (! $patient) {
            return redirect()->route('patient.login')
                ->withErrors(['token' => 'This magic link is invalid or has expired.']);
        }

        Auth::guard('patient')->login($patient);

        // Mark email as verified if not already
        if (! $patient->email_verified_at) {
            $patient->update(['email_verified_at' => now()]);
        }

        return redirect()->route('patient.dashboard')
            ->with('success', 'You have been logged in successfully!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('patient')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('patient.login')
            ->with('status', 'You have been logged out successfully.');
    }
}
