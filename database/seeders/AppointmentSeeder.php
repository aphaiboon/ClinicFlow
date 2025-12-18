<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\OrganizationRole;
use App\Models\Appointment;
use App\Models\ExamRoom;
use App\Models\Organization;
use App\Models\Patient;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('name', 'ABC Clinic')->first();

        if (! $organization) {
            return;
        }

        $patients = Patient::where('organization_id', $organization->id)->get();
        $rooms = ExamRoom::active()->get();

        // Get clinicians from the organization
        $clinicians = $organization->users()
            ->wherePivot('role', OrganizationRole::Clinician->value)
            ->get();

        if ($patients->isEmpty() || $clinicians->isEmpty()) {
            return;
        }

        // Create scheduled appointments (mix of past, present, future)
        $scheduledCount = 60;
        for ($i = 0; $i < $scheduledCount; $i++) {
            $date = fake()->dateTimeBetween('-30 days', '+60 days');
            $appointment = Appointment::factory()->create([
                'organization_id' => $organization->id,
                'patient_id' => $patients->random()->id,
                'user_id' => $clinicians->random()->id,
                'appointment_date' => $date->format('Y-m-d'),
                'appointment_time' => fake()->time('H:i:s'),
                'status' => AppointmentStatus::Scheduled,
            ]);

            // Assign exam room 75% of the time
            if ($rooms->isNotEmpty() && fake()->boolean(75)) {
                $appointment->update([
                    'exam_room_id' => $rooms->random()->id,
                ]);
            }
        }

        // Create completed appointments (all in the past)
        $completedCount = 25;
        for ($i = 0; $i < $completedCount; $i++) {
            $date = fake()->dateTimeBetween('-90 days', '-1 day');
            $appointment = Appointment::factory()->completed()->create([
                'organization_id' => $organization->id,
                'patient_id' => $patients->random()->id,
                'user_id' => $clinicians->random()->id,
                'appointment_date' => $date->format('Y-m-d'),
                'appointment_time' => fake()->time('H:i:s'),
            ]);

            // Assign exam room 80% of the time for completed
            if ($rooms->isNotEmpty() && fake()->boolean(80)) {
                $appointment->update([
                    'exam_room_id' => $rooms->random()->id,
                ]);
            }
        }

        // Create cancelled appointments (mix of past and future)
        $cancelledCount = 15;
        for ($i = 0; $i < $cancelledCount; $i++) {
            $date = fake()->dateTimeBetween('-60 days', '+30 days');
            $reasons = [
                'Patient cancelled',
                'Rescheduled',
                'No show',
                'Clinic cancelled',
                'Weather',
            ];
            $appointment = Appointment::factory()->cancelled(fake()->randomElement($reasons))->create([
                'organization_id' => $organization->id,
                'patient_id' => $patients->random()->id,
                'user_id' => $clinicians->random()->id,
                'appointment_date' => $date->format('Y-m-d'),
                'appointment_time' => fake()->time('H:i:s'),
            ]);

            // Some cancelled appointments had rooms assigned
            if ($rooms->isNotEmpty() && fake()->boolean(60)) {
                $appointment->update([
                    'exam_room_id' => $rooms->random()->id,
                ]);
            }
        }
    }
}
