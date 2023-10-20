<?php

namespace App\Enums;

enum PlanType: string
{
    case None = '';
    case Nbn = 'Nbn';
    case Opticomm = 'Opticomm';
    case Mobile = 'Mobile';
}
