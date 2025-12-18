<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (app()->environment(['local', 'staging'])) {
    Schedule::command('demo:reset-database')
        ->dailyAt('00:00')
        ->timezone('America/Los_Angeles')
        ->withoutOverlapping()
        ->onFailure(function () {
            \Log::error('Demo database reset failed');
        });
}
