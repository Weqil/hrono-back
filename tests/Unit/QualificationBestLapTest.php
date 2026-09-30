<?php

namespace Tests\Unit;

use App\Support\QualificationBestLap;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class QualificationBestLapTest extends TestCase
{
    #[Test]
    public function test_counts_manual_lap_and_following_lap_by_default(): void
    {
        $laps = [
            ['lap_number' => 1, 'lap_time_ms' => 65_000, 'timestamp_ms' => 65_000, 'is_manual' => false],
            ['lap_number' => 2, 'lap_time_ms' => 62_000, 'timestamp_ms' => 127_000, 'is_manual' => true],
            ['lap_number' => 3, 'lap_time_ms' => 61_000, 'timestamp_ms' => 188_000, 'is_manual' => false],
            ['lap_number' => 4, 'lap_time_ms' => 58_000, 'timestamp_ms' => 246_000, 'is_manual' => false],
        ];

        $this->assertTrue(QualificationBestLap::isEligibleForBest($laps, 1));
        $this->assertTrue(QualificationBestLap::isEligibleForBest($laps, 2));
        $this->assertTrue(QualificationBestLap::isEligibleForBest($laps, 3));
        $this->assertSame(58_000, QualificationBestLap::getBestLapTimeMs($laps));
    }

    #[Test]
    public function test_uses_manual_lap_as_best_when_it_is_fastest(): void
    {
        $laps = [
            ['lapTimeMs' => 65_000, 'timestampMs' => 65_000, 'isManual' => false],
            ['lapTimeMs' => 57_000, 'timestampMs' => 122_000, 'isManual' => true],
            ['lapTimeMs' => 61_000, 'timestampMs' => 183_000, 'isManual' => false],
        ];

        $this->assertSame(57_000, QualificationBestLap::getBestLapTimeMs($laps));
    }

    #[Test]
    public function test_excludes_manual_lap_and_lap_after_manual_when_disabled(): void
    {
        $laps = [
            ['lap_number' => 1, 'lap_time_ms' => 65_000, 'timestamp_ms' => 65_000, 'is_manual' => false],
            ['lap_number' => 2, 'lap_time_ms' => 62_000, 'timestamp_ms' => 127_000, 'is_manual' => true],
            ['lap_number' => 3, 'lap_time_ms' => 61_000, 'timestamp_ms' => 188_000, 'is_manual' => false],
            ['lap_number' => 4, 'lap_time_ms' => 58_000, 'timestamp_ms' => 246_000, 'is_manual' => false],
        ];

        $this->assertFalse(QualificationBestLap::isEligibleForBest($laps, 1, false));
        $this->assertFalse(QualificationBestLap::isEligibleForBest($laps, 2, false));
        $this->assertTrue(QualificationBestLap::isEligibleForBest($laps, 3, false));
        $this->assertSame(58_000, QualificationBestLap::getBestLapTimeMs($laps, false));
    }

    #[Test]
    public function test_returns_null_when_only_manual_and_following_laps_exist_and_ignored(): void
    {
        $laps = [
            ['lapTimeMs' => 62_000, 'timestampMs' => 62_000, 'isManual' => true],
            ['lapTimeMs' => 61_000, 'timestampMs' => 123_000, 'isManual' => false],
        ];

        $this->assertNull(QualificationBestLap::getBestLap($laps, false));
        $this->assertSame(61_000, QualificationBestLap::getBestLapTimeMs($laps));
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
    public function test_reference_best_uses_previous_laps_by_default(): void
    {
        $laps = [
            ['lapTimeMs' => 65_000, 'isManual' => false],
            ['lapTimeMs' => 62_000, 'isManual' => true],
            ['lapTimeMs' => 61_000, 'isManual' => false],
            ['lapTimeMs' => 58_000, 'isManual' => false],
        ];

        $this->assertSame(61_000, QualificationBestLap::referenceBestLapTimeMs($laps, 58_000));
        $this->assertSame(65_000, QualificationBestLap::referenceBestLapTimeMs($laps, 58_000, false));
    }
}
