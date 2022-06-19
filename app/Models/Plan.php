<?php

namespace App\Models;

use App\Models\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plan extends Model
{
    use HasFactory;

    protected $table = 'plans';

    protected $fillable = [
        'name',
        'type',
        'monthly_cost'
    ];

    protected $casts = [
        'mobthly_cost' => 'float',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'plan_id');
    }
}
