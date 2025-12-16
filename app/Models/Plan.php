<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Plan extends Model
{
    use HasFactory;

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function getMonthlyCostDollarsAttribute(): ?string
    {
        $cents = $this->monthly_cost;

        if ($cents === null) {
            return null;
        }

        return number_format(((int) $cents) / 100, 2, '.', '');
    }
}
