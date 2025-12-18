<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Models\Appointment;
use App\Models\ExamRoom;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'patient_id' => Patient::factory(),
            'user_id' => User::factory(),
            'exam_room_id' => null,
            'appointment_date' => fake()->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
            'appointment_time' => fake()->time('H:i:s'),
            'duration_minutes' => fake()->randomElement([15, 30, 45, 60]),
            'appointment_type' => fake()->randomElement(AppointmentType::cases()),
            'status' => AppointmentStatus::Scheduled,
            'notes' => fake()->optional()->sentence(),
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ];
    }

    public function withRoom(): static
    {
        return $this->state(fn (array $attributes) => [
            'exam_room_id' => ExamRoom::factory(),
        ]);
    }

    public function cancelled(string $reason = 'Patient request'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::Completed,
        ]);
    }
}
