<?php

namespace App\Console\Commands;

use App\Enums\OrganizationRole;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\ExamRoom;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateExistingDataToOrganizations extends Command
{
    protected $signature = 'organizations:migrate-existing-data';

    protected $description = 'Migrate existing data to default organization structure';

    public function handle(): int
    {
        $this->info('Starting data migration to organizations...');

        DB::transaction(function () {
            // Find or create default organization
            $defaultOrganization = Organization::firstOrCreate(
                ['name' => 'Default Clinic'],
                [
                    'slug' => 'default-clinic',
                    'email' => 'info@default-clinic.test',
                    'is_active' => true,
                ]
            );

            $this->info("Using organization: {$defaultOrganization->name} (ID: {$defaultOrganization->id})");

            // Migrate users
            $this->migrateUsers($defaultOrganization);

            // Migrate patients
            $this->migratePatients($defaultOrganization);

            // Migrate appointments
            $this->migrateAppointments($defaultOrganization);

            // Migrate exam rooms
            $this->migrateExamRooms($defaultOrganization);

            // Migrate audit logs
            $this->migrateAuditLogs($defaultOrganization);
        });

        $this->info('Data migration completed successfully!');

        return Command::SUCCESS;
    }

    private function migrateUsers(Organization $organization): void
    {
        $users = User::where('role', UserRole::User)
            ->whereDoesntHave('organizations', function ($query) use ($organization) {
                $query->where('organizations.id', $organization->id);
            })
            ->get();

        $count = 0;
        foreach ($users as $user) {
            $organization->users()->attach($user->id, [
                'role' => OrganizationRole::Owner->value,
                'joined_at' => now(),
            ]);

            if (! $user->current_organization_id) {
                $user->update(['current_organization_id' => $organization->id]);
            }

            $count++;
        }

        $this->info("Migrated {$count} users to organization");
    }

    private function migratePatients(Organization $organization): void
    {
        $count = Patient::whereNull('organization_id')
            ->update(['organization_id' => $organization->id]);

        $this->info("Migrated {$count} patients to organization");
    }

    private function migrateAppointments(Organization $organization): void
    {
        // First, get appointments without organization_id
        // Since we can't easily query null with foreign keys, we'll update by joining with patients
        $count = DB::table('appointments')
            ->join('patients', 'appointments.patient_id', '=', 'patients.id')
            ->where('patients.organization_id', $organization->id)
            ->whereNull('appointments.organization_id')
            ->update(['appointments.organization_id' => $organization->id]);

        $this->info("Migrated {$count} appointments to organization");
    }

    private function migrateExamRooms(Organization $organization): void
    {
        // Similar approach for exam rooms
        $count = ExamRoom::whereNull('organization_id')
            ->update(['organization_id' => $organization->id]);

        // Also handle any exam rooms that might exist without organization_id
        // This is more complex due to FK constraints, so we'll use a query builder approach
        $count += DB::table('exam_rooms')
            ->whereNull('organization_id')
            ->update(['organization_id' => $organization->id]);

        $this->info("Migrated {$count} exam rooms to organization");
    }

    private function migrateAuditLogs(Organization $organization): void
    {
        // Migrate audit logs based on user's current organization or default
        $users = User::where('current_organization_id', $organization->id)->pluck('id');

        $count = AuditLog::whereNull('organization_id')
            ->whereIn('user_id', $users)
            ->update(['organization_id' => $organization->id]);

        // Also handle audit logs for users without current_organization_id
        $usersWithoutOrg = User::whereNull('current_organization_id')
            ->where('role', UserRole::User)
            ->pluck('id');

        if ($usersWithoutOrg->isNotEmpty()) {
            $additionalCount = AuditLog::whereNull('organization_id')
                ->whereIn('user_id', $usersWithoutOrg)
                ->update(['organization_id' => $organization->id]);

            $count += $additionalCount;
        }

        $this->info("Migrated {$count} audit logs to organization");
    }
}
