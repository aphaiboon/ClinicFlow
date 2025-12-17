<?php

namespace App\Listeners;

use App\Events\AppointmentCancelled;
use App\Events\AppointmentScheduled;
use App\Events\AppointmentUpdated;
use App\Events\PatientCreated;
use App\Events\PatientUpdated;
use App\Events\RoomAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ForwardToSentinelStack implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PatientCreated|PatientUpdated|AppointmentScheduled|AppointmentUpdated|AppointmentCancelled|RoomAssigned $event): void {}
}
