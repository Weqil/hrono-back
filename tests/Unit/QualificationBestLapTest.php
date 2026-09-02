<?php

namespace Tests\Unit;

use App\Support\QualificationBestLap;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class QualificationBestLapTest extends TestCase
{
    #[Test]
    public function test_excludes_manual_lap_and_lap_after_manual(): void
    {
        $laps = [
            ['lap_number' => 1, 'lap_time_ms' => 65_000, 'timestamp_ms' => 65_000, 'is_manual' => false],
            ['lap_number' => 2, 'lap_time_ms' => 62_000, 'timestamp_ms' => 127_000, 'is_manual' => true],
            ['lap_number' => 3, 'lap_time_ms' => 61_000, 'timestamp_ms' => 188_000, 'is_manual' => false],
            ['lap_number' => 4, 'lap_time_ms' => 58_000, 'timestamp_ms' => 246_000, 'is_manual' => false],
        ];

        $this->assertFalse(QualificationBestLap::isEligibleForBest($laps, 1));
        $this->assertFalse(QualificationBestLap::isEligibleForBest($laps, 2));
        $this->assertTrue(QualificationBestLap::isEligibleForBest($laps, 3));
        $this->assertSame(58_000, QualificationBestLap::getBestLapTimeMs($laps));
    }

    #[Test]
    public function test_returns_null_when_only_manual_and_following_laps_exist(): void
    {
        $laps = [
            ['lapTimeMs' => 62_000, 'timestampMs' => 62_000, 'isManual' => true],
            ['lapTimeMs' => 61_000, 'timestampMs' => 123_000, 'isManual' => false],
        ];

        $this->assertNull(QualificationBestLap::getBestLap($laps));
    }

    #[Test]
    public function test_compare_best_laps_uses_timestamp_as_tie_breaker(): void
    {
        $lapsA = [
            ['lapTimeMs' => 60_000, 'timestampMs' => 60_000, 'isManual' => false],
            ['lapTimeMs' => 60_000, 'timestampMs' => 120_000, 'isManual' => false],
        ];
        $lapsB = [
            ['lapTimeMs' => 60_000, 'timestampMs' => 90_000, 'isManual' => false],
        ];

        $this->assertSame(-1, QualificationBestLap::compareBestLaps($lapsA, $lapsB));
    }

    #[Test]
    public function test_reference_best_uses_only_eligible_previous_laps(): void
    {
        $laps = [
            ['lapTimeMs' => 65_000, 'isManual' => false],
            ['lapTimeMs' => 62_000, 'isManual' => true],
            ['lapTimeMs' => 61_000, 'isManual' => false],
            ['lapTimeMs' => 58_000, 'isManual' => false],
        ];

        $this->assertSame(65_000, QualificationBestLap::referenceBestLapTimeMs($laps, 58_000));
    }
}
