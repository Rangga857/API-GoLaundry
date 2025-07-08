<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceLaundry extends Model
{
    use HasFactory;

    protected $table = 'services_laundry';

    protected $fillable = [
        'title',  
        'sub_title', 
        'price_per_kg',  
    ];

    protected $casts = [
        'price_per_kg' => 'integer', 
    ];
}
