<?php

namespace App\Console\Commands;

use App\Application\Arrival\Actions\AutoCloseArrivalStreamAction;
use App\Application\Arrival\Enums\CloseArrivalStreamOutcome;
use App\Models\Arrival;
use App\Support\ArrivalStreamAutoClose;
use Illuminate\Console\Command;

final class AutoCloseExpiredArrivalStreamsCommand extends Command
{
    protected $signature = 'arrivals:auto-close-expired';

    protected $description = 'Close arrival streams past duration + grace when nobody closed them';

    public function handle(AutoCloseArrivalStreamAction $action): int
    {
        $open = Arrival::query()
            ->whereNotNull('moto_stream_opened_at')
            ->whereNull('moto_stream_closed_at')
            ->orderBy('moto_stream_opened_at')
            ->get();

        // Backfill missing deadlines so delayed jobs / UI stay consistent.
        foreach ($open as $arrival) {
            if ($arrival->stream_auto_close_at !== null) {
                continue;
            }

            $dueAt = ArrivalStreamAutoClose::dueAt($arrival, $arrival->moto_stream_opened_at);
            if ($dueAt === null) {
                continue;
            }

            $arrival->forceFill(['stream_auto_close_at' => $dueAt])->save();
        }

        $due = $open->filter(static fn (Arrival $arrival): bool => $arrival->fresh()?->isStreamAutoCloseDue() ?? false);

        $closed = 0;
        $failed = 0;

        foreach ($due as $arrival) {
            $outcome = $action->execute($arrival->fresh() ?? $arrival);

            if ($outcome === CloseArrivalStreamOutcome::Closed) {
                $closed++;
            } elseif ($outcome === CloseArrivalStreamOutcome::MotoFailed) {
                $failed++;
            }
        }

        $this->info("Open={$open->count()}; due={$due->count()}; closed={$closed}; failed={$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
