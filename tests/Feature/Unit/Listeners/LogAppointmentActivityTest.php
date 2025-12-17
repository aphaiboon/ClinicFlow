<?php

use App\Enums\AuditAction;
use App\Events\AppointmentCancelled;
use App\Events\AppointmentScheduled;
use App\Events\AppointmentUpdated;
use App\Events\RoomAssigned;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->auditService = new AuditService;
});

it('logs appointment creation when AppointmentScheduled event is fired', function () {
    $appointment = Appointment::factory()->create();

    $listener = new \App\Listeners\LogAppointmentActivity($this->auditService);
    $event = new AppointmentScheduled($appointment);
    $listener->handle($event);

    $auditLog = AuditLog::where('resource_type', 'Appointment')
        ->where('resource_id', $appointment->id)
        ->where('action', AuditAction::Create)
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->user_id)->toBe($this->user->id)
        ->and($auditLog->resource_type)->toBe('Appointment')
        ->and($auditLog->resource_id)->toBe($appointment->id);
});

it('logs appointment update when AppointmentUpdated event is fired', function () {
    $appointment = Appointment::factory()->create();
    $appointment->notes = 'Updated notes';
    $appointment->save();

    $listener = new \App\Listeners\LogAppointmentActivity($this->auditService);
    $event = new AppointmentUpdated($appointment);
    $listener->handle($event);

    $auditLog = AuditLog::where('resource_type', 'Appointment')
        ->where('resource_id', $appointment->id)
        ->where('action', AuditAction::Update)
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->user_id)->toBe($this->user->id)
        ->and($auditLog->changes)->toBeArray();
});

it('logs appointment cancellation when AppointmentCancelled event is fired', function () {
    $appointment = Appointment::factory()->create();
    $originalData = $appointment->getOriginal();
    $appointment->cancellation_reason = 'Patient request';
    $appointment->save();

    $listener = new \App\Listeners\LogAppointmentActivity($this->auditService);
    $event = new AppointmentCancelled($appointment->fresh());
    $listener->handle($event);

    $auditLog = AuditLog::where('resource_type', 'Appointment')
        ->where('resource_id', $appointment->id)
        ->where('action', AuditAction::Update)
        ->orderBy('id', 'desc')
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->user_id)->toBe($this->user->id)
        ->and($auditLog->changes)->toBeArray()
        ->and(isset($auditLog->changes['before']) || isset($auditLog->changes['after']))->toBeTrue();
});

it('logs room assignment when RoomAssigned event is fired', function () {
    $appointment = Appointment::factory()->create();
    $originalData = $appointment->getOriginal();
    $appointment->exam_room_id = \App\Models\ExamRoom::factory()->create()->id;
    $appointment->save();

    $listener = new \App\Listeners\LogAppointmentActivity($this->auditService);
    $event = new RoomAssigned($appointment->fresh());
    $listener->handle($event);

    $auditLog = AuditLog::where('resource_type', 'Appointment')
        ->where('resource_id', $appointment->id)
        ->where('action', AuditAction::Update)
        ->orderBy('id', 'desc')
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->user_id)->toBe($this->user->id)
        ->and($auditLog->changes)->toBeArray()
        ->and(isset($auditLog->changes['before']) || isset($auditLog->changes['after']))->toBeTrue();
});
