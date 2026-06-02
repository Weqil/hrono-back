<?php

namespace App\Application\Arrival\Enums;

enum OpenArrivalStreamOutcome
{
    case Opened;
    case AlreadyOpened;
    case AlreadyClosed;
    case ArrivalNotFound;
    case BearerMissing;
    case MotoFailed;
}
