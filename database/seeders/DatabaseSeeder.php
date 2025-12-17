<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@clinicflow.test'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'clinician@clinicflow.test'],
            [
                'name' => 'Dr. Jane Smith',
                'password' => bcrypt('password'),
                'role' => UserRole::Clinician,
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'receptionist@clinicflow.test'],
            [
                'name' => 'Receptionist User',
                'password' => bcrypt('password'),
                'role' => UserRole::Receptionist,
                'email_verified_at' => now(),
            ]
        );

        User::factory()->count(5)->create(['role' => UserRole::Clinician]);
        User::factory()->count(3)->create(['role' => UserRole::Receptionist]);

        $this->call([
            PatientSeeder::class,
            ExamRoomSeeder::class,
            AppointmentSeeder::class,
        ]);
    }
}
