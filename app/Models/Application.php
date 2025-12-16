<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Events\ApplicationCreated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 
        'plan_id', 
        'status', 
        'address_1', 
        'address_2', 
        'city', 
        'state', 
        'postcode', 
        'order_id'
    ];

    protected $casts = [
        'status' => ApplicationStatus::class,
    ];

    protected $dispatchesEvents = [
        'created' => ApplicationCreated::class,
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function scopeFilterByPlanType(Builder $query, ?string $planType): Builder
    {
        return $query->when(
            $planType,
            fn ($query) => $query->whereHas(
                'plan', 
                fn ($query) => $query->where('type', $planType)
            )
        );
    }
}
