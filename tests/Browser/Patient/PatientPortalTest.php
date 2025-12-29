<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\PatientMagicLinkNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->organization = Organization::factory()->create(['name' => 'ABC Clinic']);
    $this->patient = Patient::factory()->for($this->organization)->create([
        'email' => 'patient@abc-clinic.test',
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
    $this->clinician = User::factory()->create();
    $this->organization->users()->attach($this->clinician->id, ['role' => \App\Enums\OrganizationRole::Clinician->value, 'joined_at' => now()]);
});

it('can complete patient login flow with magic link', function () {
    Notification::fake();

    $page = visit('/patient/login');

    $page->assertSee('Patient Login')
        ->assertSee('Enter your email to receive a magic link')
        ->type('email', $this->patient->email)
        ->press('Send Magic Link')
        ->assertSee('We have sent you a magic link');

    Notification::assertSentTo($this->patient, PatientMagicLinkNotification::class, function ($notification) {
        $token = $notification->getToken();
        $verifyPage = visit(route('patient.verify', ['token' => $token]));

        $verifyPage->assertPathIs('/patient/dashboard');

        return true;
    });
});

it('can navigate patient dashboard', function () {
    $this->actingAs($this->patient, 'patient');

    $page = visit('/patient/dashboard');

    $page->assertSee('Patient Dashboard')
        ->assertNoJavascriptErrors();
});

it('can view appointments list', function () {
    Appointment::factory()->for($this->organization)->for($this->patient)->count(3)->create([
        'status' => AppointmentStatus::Scheduled,
        'user_id' => $this->clinician->id,
    ]);

    $this->actingAs($this->patient, 'patient');

    $page = visit('/patient/appointments');

    $page->assertSee('My Appointments')
        ->assertSee('Scheduled')
        ->assertNoJavascriptErrors();
});

it('can view appointment details', function () {
    $appointment = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->addDays(2)->toDateString(),
        'appointment_time' => '10:00',
        'status' => AppointmentStatus::Scheduled,
        'user_id' => $this->clinician->id,
    ]);

    $this->actingAs($this->patient, 'patient');

    $page = visit(route('patient.appointments.show', $appointment));

    $page->assertSee('Appointment Details')
        ->assertSee($this->clinician->name)
        ->assertSee('10:00')
        ->assertNoJavascriptErrors();
});

it('can cancel appointment when allowed', function () {
    $appointment = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->addDays(2)->toDateString(),
        'appointment_time' => '10:00',
        'status' => AppointmentStatus::Scheduled,
        'user_id' => $this->clinician->id,
    ]);

    $this->actingAs($this->patient, 'patient');

    $page = visit(route('patient.appointments.show', $appointment));

    $page->assertSee('Cancel Appointment')
        ->click('Cancel Appointment')
        ->assertSee('Cancel Appointment')
        ->type('reason', 'Unable to attend')
        ->press('Confirm Cancellation')
        ->assertSee('Appointment cancelled successfully')
        ->assertNoJavascriptErrors();
});

it('can view patient profile', function () {
    $this->actingAs($this->patient, 'patient');

    $page = visit('/patient/profile');

    $page->assertSee('My Profile')
        ->assertSee($this->patient->first_name)
        ->assertSee($this->patient->last_name)
        ->assertSee($this->patient->email)
        ->assertNoJavascriptErrors();
});

it('can edit profile and save changes', function () {
    $this->actingAs($this->patient, 'patient');

    $page = visit('/patient/profile/edit');

    $page->assertSee('Edit My Profile')
        ->type('phone', '555-1234')
        ->type('address_line_1', '789 New Street')
        ->type('city', 'Chicago')
        ->press('Save Changes')
        ->assertSee('Profile updated successfully')
        ->assertNoJavascriptErrors();
});

it('can logout from patient portal', function () {
    $this->actingAs($this->patient, 'patient');

    $page = visit('/patient/dashboard');

    $page->click('Logout')
        ->assertPathIs('/patient/login')
        ->assertSee('Patient Login');
});

it('has no JavaScript errors on patient pages', function () {
    $this->actingAs($this->patient, 'patient');

    $pages = [
        visit('/patient/dashboard'),
        visit('/patient/appointments'),
        visit('/patient/profile'),
    ];

    foreach ($pages as $page) {
        $page->assertNoJavascriptErrors();
    }
});

it('shows correct cancellation eligibility on appointment details', function () {
    $cancellableAppointment = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->addDays(2)->toDateString(),
        'appointment_time' => '10:00',
        'status' => AppointmentStatus::Scheduled,
        'user_id' => $this->clinician->id,
    ]);

    $this->actingAs($this->patient, 'patient');

    $page = visit(route('patient.appointments.show', $cancellableAppointment));

    $page->assertSee('Cancel Appointment')
        ->assertNoJavascriptErrors();
});
