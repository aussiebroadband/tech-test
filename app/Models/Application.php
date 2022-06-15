<?php

namespace App\Models;

use App\Enums\ApplicationQueues;
use App\Events\ApplicationCreated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Application extends Model
{
    use HasFactory;

    protected $table = 'applications';

    protected $primaryKey = 'id';
    
    protected $fillable = [
        'queue',
        'customer_id',
        'plan_id',
        'address_1',
        'address_2',
        'city',
        'state',
        'postcode',
        'order_id',
    ];

    protected $casts = [
        'queue' => ApplicationQueues::class,
    ];

    protected $dispatchesEvents = [
        'created' => ApplicationCreated::class,
    ];
    
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
