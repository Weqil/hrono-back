<?php

namespace App\Application\Arrival\Enums;

enum SaveArrivalFinalResultsOutcome
{
    case Saved;
    case ArrivalNotFound;
    case ServerArrivalIdMismatch;
    case RaceIdMismatch;
    case StreamBearerMissing;
    case StreamCloseFailed;
}
