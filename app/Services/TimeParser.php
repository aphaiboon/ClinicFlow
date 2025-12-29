<?php

namespace App\Services;

use Carbon\Carbon;

class TimeParser
{
    /**
     * Parse a time string to a Carbon time instance.
     *
     * Supports formats:
     * - "H:i:s" (e.g., "14:30:45")
     * - "H:i" (e.g., "14:30")
     * - "H" (e.g., "14")
     *
     * @param  string  $timeString  Time string to parse
     * @return Carbon Carbon instance with parsed time
     */
    public static function parse(string $timeString): Carbon
    {
        $parts = explode(':', $timeString);

        $hour = (int) ($parts[0] ?? 0);
        $minute = (int) ($parts[1] ?? 0);
        $second = (int) ($parts[2] ?? 0);

        return Carbon::createFromTime($hour, $minute, $second);
    }
}
