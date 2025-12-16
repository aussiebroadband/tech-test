<?php

declare(strict_types=1);

namespace App\Enums;

enum PlanType: string
{
    case NBN = 'nbn';
    case Opticomm = 'opticomm';
    case Mobile = 'mobile';
}