<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plan extends Model
{
    use HasFactory;

    public function monthlyCost(): Attribute
    {
        $currency = new \NumberFormatter(
            'en_IT',
            \NumberFormatter::CURRENCY
        );

        return Attribute::make(
            get: fn ($value) => $currency->formatCurrency($value, 'USD'),
        );
    }

    public function application(): HasMany
    {
        return $this->hasMany(Application::class);
    }

}
