<?php

use App\Http\Middleware\CaptureRequestContext;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            CaptureRequestContext::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            // Check if the request is for patient routes and user is not authenticated with patient guard
            if ($request->is('patient/*') && ! \Illuminate\Support\Facades\Auth::guard('patient')->check()) {
                // If user is authenticated with web guard (staff), redirect to regular login
                if (\Illuminate\Support\Facades\Auth::guard('web')->check()) {
                    return redirect()->route('login');
                }

                // If unauthenticated, redirect to patient login
                return redirect()->route('patient.login');
            }

            return redirect()->route('login');
        });
    })->create();
