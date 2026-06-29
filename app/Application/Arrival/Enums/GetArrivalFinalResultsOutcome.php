<?php

namespace App\Application\Arrival\Enums;

enum GetArrivalFinalResultsOutcome
{
    case Found;
    case ArrivalNotFound;
    case FinalResultsNotFound;
}
