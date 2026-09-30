<?php

namespace App\Enums;

enum PlanType: string
{
    case Nbn = 'nbn';
    case Opticomm = 'opticomm';
    case Mobile = 'mobile';
}
