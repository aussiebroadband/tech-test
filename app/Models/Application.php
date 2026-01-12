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
    // Relationship to Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class,'customer_id');
    }

    // Relationship to Plan
    public function plan()
    {
        return $this->belongsTo(Plan::class,'plan_id');
    }
}
