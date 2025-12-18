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
            'name' => 'Demo Clinic',
            'email' => 'info@demo-clinic.test',
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@demo-clinic.test',
            'password' => bcrypt('password'),
            'role' => UserRole::User,
            'current_organization_id' => $organization->id,
        ]);
        $organization->users()->attach($admin->id, ['role' => OrganizationRole::Admin->value, 'joined_at' => now()]);

        $clinician = User::create([
            'name' => 'Dr. Jane Smith',
            'email' => 'jane@demo-clinic.test',
            'password' => bcrypt('password'),
            'role' => UserRole::User,
            'current_organization_id' => $organization->id,
        ]);
        $organization->users()->attach($clinician->id, ['role' => OrganizationRole::Clinician->value, 'joined_at' => now()]);

        $receptionist = User::create([
            'name' => 'Receptionist User',
            'email' => 'receptionist@demo-clinic.test',
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
    }
}
