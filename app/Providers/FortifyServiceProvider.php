<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(function (Request $request) {
            $props = [
                'canResetPassword' => Features::enabled(Features::resetPasswords()),
                'canRegister' => Features::enabled(Features::registration()),
                'status' => $request->session()->get('status'),
            ];

            if (app()->environment(['local', 'staging'])) {
                $demoUsers = \App\Models\User::query()
                    ->with(['organizations' => function ($query) {
                        $query->select('organizations.id', 'organizations.name');
                    }])
                    ->select('id', 'name', 'email', 'role', 'current_organization_id')
                    ->orderBy('name')
                    ->get()
                    ->map(function ($user) {
                        $orgRole = null;
                        $orgName = null;
                        if ($user->current_organization_id) {
                            $organization = $user->organizations
                                ->firstWhere('id', $user->current_organization_id);
                            if ($organization) {
                                $orgRole = $organization->pivot->role ?? null;
                                $orgName = $organization->name ?? null;
                            }
                        }

                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'role' => $user->role->value,
                            'organizationRole' => $orgRole,
                            'organizationName' => $orgName,
                            'type' => 'user',
                        ];
                    })
                    ->toArray();

                // Add patients to demo users list
                $demoPatients = \App\Models\Patient::query()
                    ->whereHas('organization', function ($query) {
                        $query->where('name', 'ABC Clinic');
                    })
                    ->whereNotNull('email')
                    ->select('id', 'first_name', 'last_name', 'email', 'organization_id')
                    ->orderBy('first_name')
                    ->orderBy('last_name')
                    ->get()
                    ->map(function ($patient) {
                        return [
                            'id' => $patient->id,
                            'name' => $patient->first_name.' '.$patient->last_name,
                            'email' => $patient->email,
                            'role' => 'patient',
                            'organizationRole' => null,
                            'organizationName' => $patient->organization->name ?? null,
                            'type' => 'patient',
                        ];
                    })
                    ->toArray();

                $props['demoUsers'] = array_merge($demoUsers, $demoPatients);
            }

            return Inertia::render('auth/login', $props);
        });

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/verify-email', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/register'));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/two-factor-challenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
