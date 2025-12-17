<?php

namespace App\Services\Integration;

use Illuminate\Support\Str;

class EventIdGenerator
{
    public function generate(): string
    {
        return 'evt_'.Str::ulid();
    }
}

