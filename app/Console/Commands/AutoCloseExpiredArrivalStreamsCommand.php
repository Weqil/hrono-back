<?php

namespace App\Console\Commands;

use App\Application\Arrival\Actions\AutoCloseArrivalStreamAction;
use App\Application\Arrival\Enums\CloseArrivalStreamOutcome;
use App\Models\Arrival;
use Illuminate\Console\Command;

final class AutoCloseExpiredArrivalStreamsCommand extends Command
{
    protected $signature = 'arrivals:auto-close-expired';

    protected $description = 'Close arrival streams past duration + grace when nobody closed them';

    public function handle(AutoCloseArrivalStreamAction $action): int
    {
        $due = Arrival::query()
            ->whereNotNull('moto_stream_opened_at')
            ->whereNull('moto_stream_closed_at')
            ->whereNotNull('stream_auto_close_at')
            ->where('stream_auto_close_at', '<=', now())
            ->orderBy('stream_auto_close_at')
            ->get();

        $closed = 0;
        $failed = 0;

        foreach ($due as $arrival) {
            $outcome = $action->execute($arrival);

            if ($outcome === CloseArrivalStreamOutcome::Closed) {
                $closed++;
            } elseif ($outcome === CloseArrivalStreamOutcome::MotoFailed) {
                $failed++;
            }
        }

        $this->info("Checked {$due->count()} due streams; closed={$closed}; failed={$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
