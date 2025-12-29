<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ExamRoom;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentQueryBuilder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->patient = Patient::factory()->for($this->organization)->create();
    $this->clinician = User::factory()->create();
    $this->examRoom = ExamRoom::factory()->for($this->organization)->create();
    $this->queryBuilder = new AppointmentQueryBuilder;
});

test('builds query with no filters', function () {
    Appointment::factory()->for($this->organization)->count(3)->create();

    $request = Request::create('/appointments', 'GET');
    $query = $this->queryBuilder->buildIndexQuery($request, $this->organization->id);

    expect($query->count())->toBe(3);
});

test('filters by status', function () {
    Appointment::factory()->for($this->organization)->create(['status' => AppointmentStatus::Scheduled]);
    Appointment::factory()->for($this->organization)->create(['status' => AppointmentStatus::Completed]);

    $request = Request::create('/appointments', 'GET', ['status' => 'scheduled']);
    $query = $this->queryBuilder->buildIndexQuery($request, $this->organization->id);

    expect($query->count())->toBe(1)
        ->and($query->first()->status)->toBe(AppointmentStatus::Scheduled);
});

test('filters by date', function () {
    $today = Carbon::today();
    $tomorrow = Carbon::tomorrow();

    Appointment::factory()->for($this->organization)->create(['appointment_date' => $today]);
    Appointment::factory()->for($this->organization)->create(['appointment_date' => $tomorrow]);

    $request = Request::create('/appointments', 'GET', ['date' => $today->toDateString()]);
    $query = $this->queryBuilder->buildIndexQuery($request, $this->organization->id);

    expect($query->count())->toBe(1)
        ->and($query->first()->appointment_date->toDateString())->toBe($today->toDateString());
});

test('filters by clinician', function () {
    $clinician2 = User::factory()->create();

    Appointment::factory()->for($this->organization)->create(['user_id' => $this->clinician->id]);
    Appointment::factory()->for($this->organization)->create(['user_id' => $clinician2->id]);

    $request = Request::create('/appointments', 'GET', ['clinician_id' => $this->clinician->id]);
    $query = $this->queryBuilder->buildIndexQuery($request, $this->organization->id);

    expect($query->count())->toBe(1)
        ->and($query->first()->user_id)->toBe($this->clinician->id);
});

test('filters by exam room', function () {
    $room2 = ExamRoom::factory()->for($this->organization)->create();

    Appointment::factory()->for($this->organization)->create(['exam_room_id' => $this->examRoom->id]);
    Appointment::factory()->for($this->organization)->create(['exam_room_id' => $room2->id]);

    $request = Request::create('/appointments', 'GET', ['exam_room_id' => $this->examRoom->id]);
    $query = $this->queryBuilder->buildIndexQuery($request, $this->organization->id);

    expect($query->count())->toBe(1)
        ->and($query->first()->exam_room_id)->toBe($this->examRoom->id);
});

test('ignores all filter values', function () {
    Appointment::factory()->for($this->organization)->count(2)->create();

    $request = Request::create('/appointments', 'GET', [
        'status' => 'all',
        'clinician_id' => 'all',
        'exam_room_id' => 'all',
    ]);
    $query = $this->queryBuilder->buildIndexQuery($request, $this->organization->id);

    expect($query->count())->toBe(2);
});

test('returns paginated results', function () {
    Appointment::factory()->for($this->organization)->count(20)->create();

    $request = Request::create('/appointments', 'GET');
    $paginator = $this->queryBuilder->getPaginatedAppointments($request, $this->organization->id, 10);

    expect($paginator->total())->toBe(20)
        ->and($paginator->perPage())->toBe(10)
        ->and($paginator->count())->toBe(10);
});

test('builds calendar query with date range', function () {
    $startDate = Carbon::today();
    $endDate = Carbon::today()->addDays(7);

    Appointment::factory()->for($this->organization)->create(['appointment_date' => $startDate]);
    Appointment::factory()->for($this->organization)->create(['appointment_date' => $endDate]);
    Appointment::factory()->for($this->organization)->create(['appointment_date' => $endDate->copy()->addDay()]);

    $request = Request::create('/appointments/calendar', 'GET', [
        'start_date' => $startDate->toDateString(),
        'end_date' => $endDate->toDateString(),
    ]);
    $query = $this->queryBuilder->buildCalendarQuery(
        $request,
        $this->organization->id
    );

    expect($query->count())->toBe(2);
});

test('filters calendar query by exam room', function () {
    $room2 = ExamRoom::factory()->for($this->organization)->create();

    Appointment::factory()->for($this->organization)->create([
        'exam_room_id' => $this->examRoom->id,
        'appointment_date' => Carbon::today(),
    ]);
    Appointment::factory()->for($this->organization)->create([
        'exam_room_id' => $room2->id,
        'appointment_date' => Carbon::today(),
    ]);

    $request = Request::create('/appointments/calendar', 'GET', [
        'exam_room_id' => $this->examRoom->id,
    ]);
    $query = $this->queryBuilder->buildCalendarQuery(
        $request,
        $this->organization->id
    );

    expect($query->count())->toBe(1)
        ->and($query->first()->exam_room_id)->toBe($this->examRoom->id);
});

test('filters calendar query by clinician role', function () {
    $clinician2 = User::factory()->create();

    Appointment::factory()->for($this->organization)->create([
        'user_id' => $this->clinician->id,
        'appointment_date' => Carbon::today(),
    ]);
    Appointment::factory()->for($this->organization)->create([
        'user_id' => $clinician2->id,
        'appointment_date' => Carbon::today(),
    ]);

    $request = Request::create('/appointments/calendar', 'GET');
    $query = $this->queryBuilder->buildCalendarQuery(
        $request,
        $this->organization->id,
        $this->clinician->id,
        'clinician'
    );

    expect($query->count())->toBe(1)
        ->and($query->first()->user_id)->toBe($this->clinician->id);
});

test('excludes cancelled appointments from calendar query', function () {
    Appointment::factory()->for($this->organization)->create([
        'status' => AppointmentStatus::Scheduled,
        'appointment_date' => Carbon::today(),
    ]);
    Appointment::factory()->for($this->organization)->create([
        'status' => AppointmentStatus::Cancelled,
        'appointment_date' => Carbon::today(),
    ]);

    $request = Request::create('/appointments/calendar', 'GET');
    $query = $this->queryBuilder->buildCalendarQuery(
        $request,
        $this->organization->id
    );

    expect($query->count())->toBe(1)
        ->and($query->first()->status)->toBe(AppointmentStatus::Scheduled);
});
