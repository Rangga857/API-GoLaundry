<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileAdmin extends Model
{
    use HasFactory;

    protected $table = 'profile_admin';

    protected $primaryKey = 'laundry_id'; 

    protected $fillable = [
        'user_id',
        'name',
        'address',
        'latitude',  
        'longitude',  
    ];
    
     protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float', 
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
