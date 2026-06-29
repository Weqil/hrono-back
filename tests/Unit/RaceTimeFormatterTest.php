<?php

namespace Tests\Unit;

use App\Support\RaceTimeFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RaceTimeFormatterTest extends TestCase
{
    #[DataProvider('formatProvider')]
    public function test_it_formats_milliseconds(int $milliseconds, string $expected): void
    {
        $this->assertSame($expected, RaceTimeFormatter::formatMs($milliseconds));
    }

    #[DataProvider('parseProvider')]
    public function test_it_parses_time_strings(string $value, int $expected): void
    {
        $this->assertSame($expected, RaceTimeFormatter::parseToMs($value));
    }

    public static function formatProvider(): array
    {
        return [
            [0, '00:00:00'],
            [8133000, '02:15:33'],
            [754321, '00:12:34.321'],
        ];
    }

    public static function parseProvider(): array
    {
        return [
            ['02:15:33', 8133000],
            ['00:12:34.321', 754321],
            ['12:34', 754000],
            ['754321', 754321],
        ];
    }
}
