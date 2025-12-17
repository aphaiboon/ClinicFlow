<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

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
});

require __DIR__.'/settings.php';
