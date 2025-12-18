<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create(['name' => 'ABC Clinic']);
    $this->patient = Patient::factory()->for($this->organization)->create([
        'email' => 'patient@abc-clinic.test',
    ]);
    $this->otherPatient = Patient::factory()->for($this->organization)->create();
    $this->clinician = User::factory()->create();
    $this->organization->users()->attach($this->clinician->id, ['role' => \App\Enums\OrganizationRole::Clinician->value, 'joined_at' => now()]);
});

test('patient can view their own appointments index', function () {
    Appointment::factory()->for($this->organization)->for($this->patient)->count(3)->create();

    $response = $this->actingAs($this->patient, 'patient')
        ->get(route('patient.appointments.index'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Patient/Appointments/Index')
            ->has('appointments', 3)
        );
});

test('patient can filter appointments by status', function () {
    Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'status' => AppointmentStatus::Scheduled,
    ]);
    Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'status' => AppointmentStatus::Completed,
    ]);
    Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'status' => AppointmentStatus::Cancelled,
    ]);

    $response = $this->actingAs($this->patient, 'patient')
        ->get(route('patient.appointments.index', ['status' => 'scheduled']));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Patient/Appointments/Index')
            ->has('appointments', 1)
            ->where('appointments.0.status', AppointmentStatus::Scheduled->value)
        );
});

test('patient can view their own appointment details', function () {
    $appointment = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'user_id' => $this->clinician->id,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $response = $this->actingAs($this->patient, 'patient')
        ->get(route('patient.appointments.show', $appointment));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Patient/Appointments/Show')
            ->where('appointment.id', $appointment->id)
            ->where('appointment.user.id', $this->clinician->id)
        );
});

test('patient cannot view other patients appointments', function () {
    $otherPatientAppointment = Appointment::factory()->for($this->organization)->for($this->otherPatient)->create();

    $response = $this->actingAs($this->patient, 'patient')
        ->get(route('patient.appointments.show', $otherPatientAppointment));

    $response->assertForbidden();
});

test('patient can cancel appointment when allowed', function () {
    $appointment = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->addDays(2)->toDateString(),
        'appointment_time' => '10:00',
        'status' => AppointmentStatus::Scheduled,
        'user_id' => $this->clinician->id,
    ]);

    $response = $this->actingAs($this->patient, 'patient')
        ->post(route('patient.appointments.cancel', $appointment), [
            'reason' => 'Patient request',
        ]);

    $response->assertRedirect(route('patient.appointments.show', $appointment));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'status' => AppointmentStatus::Cancelled->value,
        'cancellation_reason' => 'Patient request',
    ]);
});

test('patient cannot cancel appointment too close to appointment time', function () {
    $appointment = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->addHours(12)->toDateString(),
        'appointment_time' => now()->addHours(12)->format('H:i'),
        'status' => AppointmentStatus::Scheduled,
        'user_id' => $this->clinician->id,
    ]);

    $response = $this->actingAs($this->patient, 'patient')
        ->post(route('patient.appointments.cancel', $appointment), [
            'reason' => 'Patient request',
        ]);

    $response->assertSessionHasErrors('cancellation');
    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'status' => AppointmentStatus::Scheduled->value,
    ]);
});

test('patient cannot cancel already cancelled appointment', function () {
    $appointment = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->addDays(2)->toDateString(),
        'appointment_time' => '10:00',
        'status' => AppointmentStatus::Cancelled,
        'user_id' => $this->clinician->id,
    ]);

    $response = $this->actingAs($this->patient, 'patient')
        ->post(route('patient.appointments.cancel', $appointment), [
            'reason' => 'Patient request',
        ]);

    $response->assertSessionHasErrors('cancellation');
});

test('patient cannot cancel completed appointment', function () {
    $appointment = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->subDay()->toDateString(),
        'appointment_time' => '10:00',
        'status' => AppointmentStatus::Completed,
        'user_id' => $this->clinician->id,
    ]);

    $response = $this->actingAs($this->patient, 'patient')
        ->post(route('patient.appointments.cancel', $appointment), [
            'reason' => 'Patient request',
        ]);

    $response->assertSessionHasErrors('cancellation');
});

test('appointment cancellation includes reason', function () {
    $appointment = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->addDays(2)->toDateString(),
        'appointment_time' => '10:00',
        'status' => AppointmentStatus::Scheduled,
        'user_id' => $this->clinician->id,
    ]);

    $reason = 'Unable to attend due to personal reasons';

    $this->actingAs($this->patient, 'patient')
        ->post(route('patient.appointments.cancel', $appointment), [
            'reason' => $reason,
        ]);

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'cancellation_reason' => $reason,
    ]);
});

test('patient sees correct cancellation eligibility status', function () {
    $cancellableAppointment = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->addDays(2)->toDateString(),
        'appointment_time' => '10:00',
        'status' => AppointmentStatus::Scheduled,
        'user_id' => $this->clinician->id,
    ]);

    $nonCancellableAppointment = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->addHours(12)->toDateString(),
        'appointment_time' => now()->addHours(12)->format('H:i'),
        'status' => AppointmentStatus::Scheduled,
        'user_id' => $this->clinician->id,
    ]);

    $response1 = $this->actingAs($this->patient, 'patient')
        ->get(route('patient.appointments.show', $cancellableAppointment));

    $response1->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('canCancel', true)
        );

    $response2 = $this->actingAs($this->patient, 'patient')
        ->get(route('patient.appointments.show', $nonCancellableAppointment));

    $response2->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('canCancel', false)
        );
});

test('unauthenticated users cannot access appointment routes', function () {
    $appointment = Appointment::factory()->for($this->organization)->for($this->patient)->create();

    $response1 = $this->get(route('patient.appointments.index'));
    $response1->assertRedirect(route('patient.login'));

    $response2 = $this->get(route('patient.appointments.show', $appointment));
    $response2->assertRedirect(route('patient.login'));

    $response3 = $this->post(route('patient.appointments.cancel', $appointment));
    $response3->assertRedirect(route('patient.login'));
});

test('staff cannot access patient appointment routes', function () {
    $user = User::factory()->create();
    $appointment = Appointment::factory()->for($this->organization)->for($this->patient)->create();

    $response1 = $this->actingAs($user)->get(route('patient.appointments.index'));
    $response1->assertRedirect(route('login'));

    $response2 = $this->actingAs($user)->get(route('patient.appointments.show', $appointment));
    $response2->assertRedirect(route('login'));

    $response3 = $this->actingAs($user)->post(route('patient.appointments.cancel', $appointment));
    $response3->assertRedirect(route('login'));
});

test('appointment cancellation creates audit log', function () {
    $appointment = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->addDays(2)->toDateString(),
        'appointment_time' => '10:00',
        'status' => AppointmentStatus::Scheduled,
        'user_id' => $this->clinician->id,
    ]);

    $this->actingAs($this->patient, 'patient')
        ->post(route('patient.appointments.cancel', $appointment), [
            'reason' => 'Patient request',
        ]);

    $this->assertDatabaseHas('audit_logs', [
        'resource_type' => 'Appointment',
        'resource_id' => $appointment->id,
        'action' => 'update',
        'metadata->patient_id' => $this->patient->id,
    ]);
});

