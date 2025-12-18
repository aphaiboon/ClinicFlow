<?php

namespace Database\Seeders;

use App\Enums\OrganizationRole;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@clinicflow.test',
            'password' => bcrypt('password'),
            'role' => UserRole::SuperAdmin,
        ]);

        $organization = Organization::factory()->create([
            'name' => 'ABC Clinic',
            'email' => 'info@abc-clinic.test',
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@abc-clinic.test',
            'password' => bcrypt('password'),
            'role' => UserRole::User,
            'current_organization_id' => $organization->id,
        ]);
        $organization->users()->attach($admin->id, ['role' => OrganizationRole::Admin->value, 'joined_at' => now()]);

        $clinician = User::create([
            'name' => 'Dr. Jane Smith',
            'email' => 'jane@abc-clinic.test',
            'password' => bcrypt('password'),
            'role' => UserRole::User,
            'current_organization_id' => $organization->id,
        ]);
        $organization->users()->attach($clinician->id, ['role' => OrganizationRole::Clinician->value, 'joined_at' => now()]);

        $receptionist = User::create([
            'name' => 'Receptionist User',
            'email' => 'receptionist@abc-clinic.test',
            'password' => bcrypt('password'),
            'role' => UserRole::User,
            'current_organization_id' => $organization->id,
        ]);
        $organization->users()->attach($receptionist->id, ['role' => OrganizationRole::Receptionist->value, 'joined_at' => now()]);

        User::factory()
            ->count(5)
            ->create(['role' => UserRole::User])
            ->each(function ($user) use ($organization) {
                $organization->users()->attach($user->id, [
                    'role' => OrganizationRole::Clinician->value,
                    'joined_at' => now(),
                ]);
                $user->update(['current_organization_id' => $organization->id]);
            });

        User::factory()
            ->count(3)
            ->create(['role' => UserRole::User])
            ->each(function ($user) use ($organization) {
                $organization->users()->attach($user->id, [
                    'role' => OrganizationRole::Receptionist->value,
                    'joined_at' => now(),
                ]);
                $user->update(['current_organization_id' => $organization->id]);
            });

        // Seed exam rooms (required before appointments)
        $this->call(ExamRoomSeeder::class);

        // Seed patients for ABC Clinic
        $this->call(PatientSeeder::class);

        // Seed appointments (requires patients, users, and exam rooms to exist)
        $this->call(AppointmentSeeder::class);
    }
}
