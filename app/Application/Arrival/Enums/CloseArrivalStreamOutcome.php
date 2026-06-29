<?php

namespace App\Application\Arrival\Enums;

enum CloseArrivalStreamOutcome
{
    case Closed;
    case NotOpened;
    case ArrivalNotFound;
    case BearerMissing;
    case MotoFailed;
}
