<?php

namespace App\Filament\Support;

use App\Support\RaceTimeFormatter;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Validation\ValidationException;

final class RaceTimeForm
{
    private const TIME_MASK = '99:99:99.999';

    private const TIME_PLACEHOLDER = '00:00:00.000';

    public static function totalTimeMsInput(string $name = 'total_time_ms', string $label = 'Время'): TextInput
    {
        return self::timeMsInput($name, $label, required: true);
    }

    public static function bestLapTimeMsInput(string $name = 'best_lap_time_ms', string $label = 'Лучший круг'): TextInput
    {
        return self::applyTimeMask(
            TextInput::make($name)
                ->label($label)
                ->afterStateHydrated(function (TextInput $component, $state): void {
                    if (filled($state) && (int) $state > 0) {
                        $component->state(RaceTimeFormatter::formatMs((int) $state));
                    }
                })
                ->dehydrateStateUsing(function ($state) use ($name): int {
                    if (! filled($state)) {
                        return 0;
                    }

                    $milliseconds = RaceTimeFormatter::parseToMs(is_string($state) ? $state : (string) $state);

                    if ($milliseconds === null) {
                        throw ValidationException::withMessages([
                            $name => 'Укажите время в формате ЧЧ:ММ:СС.ммм.',
                        ]);
                    }

                    return $milliseconds;
                }),
        );
    }

    public static function totalTimeMsColumn(string $name = 'total_time_ms', string $label = 'Время'): TextColumn
    {
        return TextColumn::make($name)
            ->label($label)
            ->formatStateUsing(fn (?int $state): string => RaceTimeFormatter::formatMs($state))
            ->sortable();
    }

    private static function timeMsInput(string $name, string $label, bool $required): TextInput
    {
        $field = self::applyTimeMask(
            TextInput::make($name)
                ->label($label)
                ->afterStateHydrated(function (TextInput $component, $state): void {
                    if (filled($state)) {
                        $component->state(RaceTimeFormatter::formatMs((int) $state));
                    }
                })
                ->dehydrateStateUsing(function ($state) use ($name): int {
                    $milliseconds = RaceTimeFormatter::parseToMs(is_string($state) ? $state : (string) $state);

                    if ($milliseconds === null) {
                        throw ValidationException::withMessages([
                            $name => 'Укажите время в формате ЧЧ:ММ:СС.ммм.',
                        ]);
                    }

                    return $milliseconds;
                }),
        );

        if ($required) {
            $field->required();
        }

        return $field;
    }

    private static function applyTimeMask(TextInput $field): TextInput
    {
        return $field
            ->mask(self::TIME_MASK)
            ->placeholder(self::TIME_PLACEHOLDER);
    }
}
