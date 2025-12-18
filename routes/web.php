<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('register', [\App\Http\Controllers\OrganizationRegistrationController::class, 'create'])
        ->name('organization.register');
    Route::post('register', [\App\Http\Controllers\OrganizationRegistrationController::class, 'store'])
        ->name('organization.register.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::resource('patients', \App\Http\Controllers\PatientController::class);
    Route::resource('appointments', \App\Http\Controllers\AppointmentController::class);
    Route::post('appointments/{appointment}/cancel', [\App\Http\Controllers\AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::post('appointments/{appointment}/assign-room', [\App\Http\Controllers\AppointmentController::class, 'assignRoom'])->name('appointments.assign-room');
    Route::resource('exam-rooms', \App\Http\Controllers\ExamRoomController::class);
    Route::post('exam-rooms/{examRoom}/activate', [\App\Http\Controllers\ExamRoomController::class, 'activate'])->name('exam-rooms.activate');
    Route::post('exam-rooms/{examRoom}/deactivate', [\App\Http\Controllers\ExamRoomController::class, 'deactivate'])->name('exam-rooms.deactivate');
    Route::get('audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('audit-logs/{auditLog}', [\App\Http\Controllers\AuditLogController::class, 'show'])->name('audit-logs.show');

    Route::middleware(\App\Http\Middleware\RequireSuperAdmin::class)->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\SuperAdmin\SuperAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('organizations', [\App\Http\Controllers\SuperAdmin\OrganizationController::class, 'index'])->name('organizations.index');
        Route::get('organizations/{organization}', [\App\Http\Controllers\SuperAdmin\OrganizationController::class, 'show'])->name('organizations.show');
        Route::get('users', [\App\Http\Controllers\SuperAdmin\UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [\App\Http\Controllers\SuperAdmin\UserController::class, 'show'])->name('users.show');
    });
});

require __DIR__.'/settings.php';
