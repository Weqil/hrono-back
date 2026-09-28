<?php

namespace App\Support;

use App\Models\Arrival;
use Illuminate\Support\Carbon;

final class ArrivalStreamAutoClose
{
    public static function graceSeconds(): int
    {
        $minutes = (int) config('hrono.stream_auto_close_grace_minutes', 10);

        return max(0, $minutes) * 60;
    }

    public static function dueAt(Arrival $arrival, Carbon|\DateTimeInterface $openedAt): ?Carbon
    {
        $durationSeconds = ArrivalDurationParser::toSeconds($arrival->time);

        if ($durationSeconds === null) {
            $durationSeconds = 0;
        }

        return Carbon::parse($openedAt)->addSeconds($durationSeconds + self::graceSeconds());
    }
}
