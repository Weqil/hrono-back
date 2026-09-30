<?php

namespace App\Support;

use App\Models\Arrival;
use Illuminate\Support\Carbon;

/**
 * Auto-close Mototrek stream after race duration + idle grace.
 *
 * Initial deadline: opened_at + arrival.time + grace.
 * Each live-results packet while the stream is open pushes deadline to
 * max(opened_at + duration, now) + grace (idle keep-alive).
 */
final class ArrivalStreamAutoClose
{
    public static function graceSeconds(): int
    {
        $minutes = (int) config('hrono.stream_auto_close_grace_minutes', 10);

        return max(0, $minutes) * 60;
    }

    public static function raceEndAt(Arrival $arrival, Carbon|\DateTimeInterface $openedAt): Carbon
    {
        $durationSeconds = ArrivalDurationParser::toSeconds($arrival->time);

        if ($durationSeconds === null) {
            $durationSeconds = 0;
        }

        return Carbon::parse($openedAt)->addSeconds($durationSeconds);
    }

    public static function dueAt(Arrival $arrival, Carbon|\DateTimeInterface $openedAt): Carbon
    {
        return self::raceEndAt($arrival, $openedAt)->addSeconds(self::graceSeconds());
    }

    /**
     * Next auto-close instant after activity at $at (idle grace from race end or now).
     */
    public static function dueAfterActivity(
        Arrival $arrival,
        Carbon|\DateTimeInterface $at,
    ): ?Carbon {
        if ($arrival->moto_stream_opened_at === null) {
            return null;
        }

        $at = Carbon::parse($at);
        $raceEnd = self::raceEndAt($arrival, $arrival->moto_stream_opened_at);
        $anchor = $at->greaterThan($raceEnd) ? $at : $raceEnd;

        return $anchor->copy()->addSeconds(self::graceSeconds());
    }

    /**
     * Extends stream_auto_close_at when live activity arrives. Never shrinks the deadline.
     *
     * @return Carbon|null the deadline after this call (unchanged or extended)
     */
    public static function extendFromActivity(
        Arrival $arrival,
        Carbon|\DateTimeInterface|null $at = null,
    ): ?Carbon {
        if ($arrival->moto_stream_opened_at === null || $arrival->moto_stream_closed_at !== null) {
            return $arrival->stream_auto_close_at;
        }

        $newDue = self::dueAfterActivity($arrival, $at ?? now());

        if ($newDue === null) {
            return null;
        }

        $current = $arrival->stream_auto_close_at;
        if ($current !== null && $current->greaterThanOrEqualTo($newDue)) {
            return $current;
        }

        $arrival->forceFill(['stream_auto_close_at' => $newDue])->save();

        return $newDue;
    }
}
