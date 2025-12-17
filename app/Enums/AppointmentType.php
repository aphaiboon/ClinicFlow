<?php

namespace App\Enums;

enum AppointmentType: string
{
    case Routine = 'routine';
    case FollowUp = 'follow_up';
    case Consultation = 'consultation';
    case Emergency = 'emergency';
}
