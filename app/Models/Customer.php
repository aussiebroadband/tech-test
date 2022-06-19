<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'mobile'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'plan_id');
    }

    private function getFullName() {
        return $this->first_name.' '.$this->lastName;
    }
}
