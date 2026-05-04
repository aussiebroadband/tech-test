<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Events\ApplicationCreated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Application extends Model
{
    use HasFactory;

    protected $casts = [
        'status' => ApplicationStatus::class,
    ];

    protected $dispatchesEvents = [
        'created' => ApplicationCreated::class,
    ];
}
