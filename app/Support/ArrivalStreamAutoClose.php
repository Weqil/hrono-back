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
 *
 * Hard cap (default 24h from opened_at) never moves — live activity cannot
 * keep a forgotten stream open past it.
 */
final class ArrivalStreamAutoClose
{
    public static function graceSeconds(): int
    {
        $minutes = (int) config('hrono.stream_auto_close_grace_minutes', 10);

        return max(0, $minutes) * 60;
    }

    public static function maxSeconds(): int
    {
        $hours = (int) config('hrono.stream_auto_close_max_hours', 24);

        return max(1, $hours) * 3600;
    }

    public static function hardCapAt(Carbon|\DateTimeInterface $openedAt): Carbon
    {
        return Carbon::parse($openedAt)->addSeconds(self::maxSeconds());
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
        return self::clampToHardCap(
            self::raceEndAt($arrival, $openedAt)->addSeconds(self::graceSeconds()),
            $openedAt,
        );
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

        return self::clampToHardCap(
            $anchor->copy()->addSeconds(self::graceSeconds()),
            $arrival->moto_stream_opened_at,
        );
    }

    /**
     * Extends stream_auto_close_at when live activity arrives. Never shrinks the deadline.
     * Never extends past the hard cap.
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

        $openedAt = $arrival->moto_stream_opened_at;
        $hardCap = self::hardCapAt($openedAt);
        $at = Carbon::parse($at ?? now());

        // Past hard cap: freeze deadline at hard cap (auto-close is due).
        if ($hardCap->lessThanOrEqualTo($at)) {
            if ($arrival->stream_auto_close_at === null
                || $arrival->stream_auto_close_at->greaterThan($hardCap)
            ) {
                $arrival->forceFill(['stream_auto_close_at' => $hardCap])->save();
            }

            return $hardCap;
        }

        $newDue = self::dueAfterActivity($arrival, $at);

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

    public static function clampToHardCap(
        Carbon $due,
        Carbon|\DateTimeInterface $openedAt,
    ): Carbon {
        $hardCap = self::hardCapAt($openedAt);

        return $due->greaterThan($hardCap) ? $hardCap->copy() : $due;
    }
}
