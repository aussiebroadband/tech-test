<?php

namespace App\Models;

use App\Enums\PlanType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $casts = [
        'type' => PlanType::class,
        'monthly_cost' => 'integer',
    ];

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * The monthly cost (stored in cents) as a human readable dollar amount, e.g. "$1,234.50".
     */
    protected function formattedMonthlyCost(): Attribute
    {
        return Attribute::get(fn (): string => '$'.number_format($this->monthly_cost / 100, 2));
    }
}
