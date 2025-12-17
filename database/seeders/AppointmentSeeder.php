<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ExamRoom;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $patients = Patient::all();
        $users = User::all();
        $rooms = ExamRoom::active()->get();

        if ($patients->isEmpty() || $users->isEmpty()) {
            return;
        }

        Appointment::factory()
            ->count(100)
            ->create()
            ->each(function ($appointment) use ($rooms) {
                if ($rooms->isNotEmpty() && fake()->boolean(70)) {
                    $appointment->update([
                        'exam_room_id' => $rooms->random()->id,
                    ]);
                }
            });

        Appointment::factory()
            ->count(10)
            ->cancelled('Patient cancelled')
            ->create();

        Appointment::factory()
            ->count(15)
            ->completed()
            ->create();
    }
}
