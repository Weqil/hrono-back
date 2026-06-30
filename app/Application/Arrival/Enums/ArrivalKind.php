<?php

namespace App\Application\Arrival\Enums;

enum ArrivalKind: string
{
    case Qualification = 'qualification';
    case Regular = 'regular';

    public function label(): string
    {
        return match ($this) {
            self::Qualification => 'Квалификационный',
            self::Regular => 'Обычный',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    public static function tryFromSlug(?string $slug): ?self
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        return self::tryFrom($slug);
    }
}
