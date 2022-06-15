<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plan extends Model
{
    use HasFactory;

    protected $table = 'plans';

    protected $primaryKey = 'id';
    
    protected $fillable = [
        'name',
        'type',
        'monthly_cost',
    ];

    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}
