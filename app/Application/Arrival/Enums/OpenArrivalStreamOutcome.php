<?php

namespace App\Application\Arrival\Enums;

enum OpenArrivalStreamOutcome
{
    case Opened;
    case ArrivalNotFound;
    case BearerMissing;
    case MotoFailed;
}
