<?php

namespace App\Jobs;

use App\Application\Arrival\Actions\AutoCloseArrivalStreamAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class AutoCloseArrivalStreamJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $arrivalId,
    ) {}

    public function handle(AutoCloseArrivalStreamAction $action): void
    {
        $outcome = $action->execute($this->arrivalId);

        Log::channel('info')->info('arrivals.stream.auto_close_job', [
            'arrival_id' => $this->arrivalId,
            'outcome' => $outcome->name,
        ]);
    }
}
