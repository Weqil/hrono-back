<?php

namespace Tests\Unit;

use App\Support\ArrivalDurationParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ArrivalDurationParserTest extends TestCase
{
    #[Test]
    #[DataProvider('durationProvider')]
    public function test_it_parses_duration(mixed $input, ?int $expected): void
    {
        $this->assertSame($expected, ArrivalDurationParser::toSeconds($input));
    }

    /**
     * @return array<string, array{0:mixed, 1:?int}>
     */
    public static function durationProvider(): array
    {
        return [
            'hhmmss' => ['00:10:00', 600],
            'mmss' => ['10:00', 600],
            'one_minute' => ['01:00', 60],
            'seconds_number' => [90, 90],
            'seconds_string' => ['90', 90],
            'invalid' => ['abc', null],
            'empty' => ['', null],
            'null' => [null, null],
        ];
    }
}
