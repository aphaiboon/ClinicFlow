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

test('authenticated patient can access dashboard', function () {
    $response = $this->actingAs($this->patient, 'patient')
        ->get(route('patient.dashboard'));

    $response->assertOk()
        ->assertInertia(fn(Assert $page) => $page->component('Patient/Dashboard'));
});

test('dashboard displays only patients own appointments', function () {
    $patientAppointment = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->addDay(),
        'status' => AppointmentStatus::Scheduled,
    ]);

    $otherPatientAppointment = Appointment::factory()->for($this->organization)->for($this->otherPatient)->create([
        'appointment_date' => now()->addDay(),
        'status' => AppointmentStatus::Scheduled,
    ]);

    $response = $this->actingAs($this->patient, 'patient')
        ->get(route('patient.dashboard'));

    $response->assertOk()
        ->assertInertia(
            fn(Assert $page) => $page
                ->component('Patient/Dashboard')
                ->has('upcomingAppointments', 1)
                ->where('upcomingAppointments.0.id', $patientAppointment->id)
        );

    // Verify the other patient's appointment is not in the list
    // Since we have exactly 1 appointment and it's the patient's, the other is excluded
    $upcomingIds = collect($response->viewData('page')['props']['upcomingAppointments'])
        ->pluck('id')
        ->toArray();
    expect($upcomingIds)->toHaveCount(1)
        ->and($upcomingIds)->toContain($patientAppointment->id)
        ->and($upcomingIds)->not->toContain($otherPatientAppointment->id);
});

test('dashboard shows upcoming appointments correctly', function () {
    $upcomingAppointment1 = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->addDays(2)->toDateString(),
        'appointment_time' => '10:00',
        'status' => AppointmentStatus::Scheduled,
    ]);

    $upcomingAppointment2 = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->addDays(3)->toDateString(),
        'appointment_time' => '14:00',
        'status' => AppointmentStatus::Scheduled,
    ]);

    $response = $this->actingAs($this->patient, 'patient')
        ->get(route('patient.dashboard'));

    $response->assertOk()
        ->assertInertia(
            fn(Assert $page) => $page
                ->component('Patient/Dashboard')
                ->has('upcomingAppointments', 2)
                ->where('upcomingAppointments.0.id', $upcomingAppointment1->id)
                ->where('upcomingAppointments.1.id', $upcomingAppointment2->id)
        );
});

test('dashboard shows recent appointments correctly', function () {
    $recentAppointment1 = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->subDays(2)->toDateString(),
        'appointment_time' => '10:00',
        'status' => AppointmentStatus::Completed,
    ]);

    $recentAppointment2 = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->subDays(1)->toDateString(),
        'appointment_time' => '14:00',
        'status' => AppointmentStatus::Completed,
    ]);

    $response = $this->actingAs($this->patient, 'patient')
        ->get(route('patient.dashboard'));

    $response->assertOk()
        ->assertInertia(
            fn(Assert $page) => $page
                ->component('Patient/Dashboard')
                ->has('recentAppointments', 2)
                ->where('recentAppointments.0.id', $recentAppointment2->id)
                ->where('recentAppointments.1.id', $recentAppointment1->id)
        );
});

test('dashboard excludes cancelled appointments from upcoming', function () {
    Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->addDays(2)->toDateString(),
        'status' => AppointmentStatus::Cancelled,
    ]);

    $scheduledAppointment = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->addDays(3)->toDateString(),
        'status' => AppointmentStatus::Scheduled,
    ]);

    $response = $this->actingAs($this->patient, 'patient')
        ->get(route('patient.dashboard'));

    $response->assertOk()
        ->assertInertia(
            fn(Assert $page) => $page
                ->component('Patient/Dashboard')
                ->has('upcomingAppointments', 1)
                ->where('upcomingAppointments.0.id', $scheduledAppointment->id)
        );
});

test('dashboard displays correct appointment counts', function () {
    Appointment::factory()->for($this->organization)->for($this->patient)->count(3)->create([
        'appointment_date' => now()->addDays(2)->toDateString(),
        'status' => AppointmentStatus::Scheduled,
    ]);

    Appointment::factory()->for($this->organization)->for($this->patient)->count(2)->create([
        'appointment_date' => now()->subDays(2)->toDateString(),
        'status' => AppointmentStatus::Completed,
    ]);

    $response = $this->actingAs($this->patient, 'patient')
        ->get(route('patient.dashboard'));

    $response->assertOk()
        ->assertInertia(
            fn(Assert $page) => $page
                ->component('Patient/Dashboard')
                ->has('upcomingAppointments', 3)
                ->has('recentAppointments', 2)
        );
});

test('guest cannot access patient dashboard', function () {
    $response = $this->get(route('patient.dashboard'));

    $response->assertRedirect(route('patient.login'));
    $this->assertGuest('patient');
});

test('staff cannot access patient dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('patient.dashboard'));

    $response->assertRedirect(route('login'));
    $this->assertGuest('patient');
});

test('dashboard loads with proper Inertia props', function () {
    Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->addDay(),
        'status' => AppointmentStatus::Scheduled,
        'user_id' => $this->clinician->id,
    ]);

    $response = $this->actingAs($this->patient, 'patient')
        ->get(route('patient.dashboard'));

    $response->assertOk()
        ->assertInertia(
            fn(Assert $page) => $page
                ->component('Patient/Dashboard')
                ->has('upcomingAppointments')
                ->has('recentAppointments')
                ->where('upcomingAppointments.0.user.id', $this->clinician->id)
        );
});

test('dashboard handles empty appointment states', function () {
    $response = $this->actingAs($this->patient, 'patient')
        ->get(route('patient.dashboard'));

    $response->assertOk()
        ->assertInertia(
            fn(Assert $page) => $page
                ->component('Patient/Dashboard')
                ->has('upcomingAppointments', 0)
                ->has('recentAppointments', 0)
        );
});
