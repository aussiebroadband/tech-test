<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\PlanType;
use App\Events\ApplicationCreated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    use HasFactory;

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

    /**
     * Limit the query to applications whose plan is of the given type.
     */
    public function scopeOfPlanType(Builder $query, PlanType $type): void
    {
        $query->whereHas('plan', fn (Builder $plan) => $plan->where('type', $type));
    }

    /**
     * Limit the query to applications with the given status.
     */
    public function scopeWithStatus(Builder $query, ApplicationStatus $status): void
    {
        $query->where('status', $status);
    }

    /**
     * Record a successful B2B order and move the application to complete.
     */
    public function markAsOrdered(string $orderId): void
    {
        $this->order_id = $orderId;
        $this->status = ApplicationStatus::Complete;
        $this->save();
    }

    /**
     * Move the application to order failed after a failed B2B order or error.
     */
    public function markAsOrderFailed(): void
    {
        $this->status = ApplicationStatus::OrderFailed;
        $this->save();
    }
}
