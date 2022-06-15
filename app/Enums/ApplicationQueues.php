<?php

namespace App\Enums;

enum ApplicationQueues: string
{
    case Prelim = 'prelim';
    case PaymentRequired = 'payment required';
    case Order = 'order';
    case OrderFailed = 'order failed';
    case Complete = 'complete';
}
