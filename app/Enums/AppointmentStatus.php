<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function isCancellable(): bool
    {
        return $this === self::Scheduled;
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Scheduled, self::InProgress], true);
    }
}
