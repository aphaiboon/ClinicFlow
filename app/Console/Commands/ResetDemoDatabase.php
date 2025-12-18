<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ResetDemoDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:reset-database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the database with fresh migrations and seed data (demo/staging only)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! app()->environment(['local', 'staging'])) {
            $this->error('This command can only be run in local or staging environments.');

            return Command::FAILURE;
        }

        $this->info('Resetting demo database...');
        $this->warn('This will drop all tables and re-run migrations with seed data.');

        try {
            Artisan::call('migrate:fresh', [
                '--seed' => true,
                '--force' => true,
            ]);

            $this->info('Database reset completed successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to reset database: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
