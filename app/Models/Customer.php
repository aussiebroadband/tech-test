<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Customer extends Model
{
    use HasFactory;

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function getFullNameAttribute(): ?string
    {
        $name = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));
        return $name === '' ? null : $name;
    }
}
