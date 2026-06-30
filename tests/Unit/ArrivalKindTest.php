<?php

namespace Tests\Unit;

use App\Application\Arrival\Enums\ArrivalKind;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ArrivalKindTest extends TestCase
{
    #[DataProvider('labelProvider')]
    public function test_it_has_russian_labels(ArrivalKind $kind, string $label): void
    {
        $this->assertSame($label, $kind->label());
    }

    public function test_it_has_two_cases(): void
    {
        $this->assertCount(2, ArrivalKind::cases());
    }

    public static function labelProvider(): array
    {
        return [
            [ArrivalKind::Qualification, 'Квалификационный'],
            [ArrivalKind::Regular, 'Обычный'],
        ];
    }
}
