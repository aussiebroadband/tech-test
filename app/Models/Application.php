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

    protected $fillable = [
        'status',
        'order_id',
        'customer_id',
        'plan_id',
        'address_1',
        'address_2',
        'city',
        'state',
        'postcode',
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

    public function scopeWithRelations($query)
    {
        return $query->with(['customer', 'plan']);
    }

    public function scopeForPlanType($query, ?string $planType)
    {
        if ($planType) {
            return $query->whereHas('plan', fn ($q) => $q->where('type', $planType));
        }

        return $query;
    }

    public function scopeReadyForNbnOrdering($query)
    {
        return $query
            ->where('status', ApplicationStatus::Order)
            ->whereHas('plan', fn ($q) => $q->where('type', 'nbn'));
    }
}