<?php

namespace App\Providers;

use App\Events\AppointmentCancelled;
use App\Events\AppointmentScheduled;
use App\Events\AppointmentUpdated;
use App\Events\PatientCreated;
use App\Events\PatientUpdated;
use App\Events\RoomAssigned;
use App\Listeners\ForwardToSentinelStack;
use App\Listeners\LogAppointmentActivity;
use App\Listeners\LogPatientActivity;
use App\Services\Integration\NullSentinelStackClient;
use App\Services\Integration\SentinelStackClientInterface;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $listen = [
        PatientCreated::class => [
            LogPatientActivity::class,
            ForwardToSentinelStack::class,
        ],
        PatientUpdated::class => [
            LogPatientActivity::class,
            ForwardToSentinelStack::class,
        ],
        AppointmentScheduled::class => [
            LogAppointmentActivity::class,
            ForwardToSentinelStack::class,
        ],
        AppointmentUpdated::class => [
            LogAppointmentActivity::class,
            ForwardToSentinelStack::class,
        ],
        AppointmentCancelled::class => [
            LogAppointmentActivity::class,
            ForwardToSentinelStack::class,
        ],
        RoomAssigned::class => [
            LogAppointmentActivity::class,
            ForwardToSentinelStack::class,
        ],
    ];

    public function register(): void
    {
        $this->app->singleton(
            SentinelStackClientInterface::class,
            fn () => new NullSentinelStackClient
        );
    }

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
