<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';

    protected $primaryKey = 'id';
    
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'mobile'
    ];

    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}
