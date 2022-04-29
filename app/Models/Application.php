<?php

namespace App\Models;

use App\Enums\ApplicationQueues;
use App\Events\ApplicationCreated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Application extends Model
{
    use HasFactory;

    protected $casts = [
        'queue' => ApplicationQueues::class,
    ];

    protected $dispatchesEvents = [
        'created' => ApplicationCreated::class,
    ];
}
