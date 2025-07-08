<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfilePelanggan extends Model
{
    use HasFactory;

    protected $table = 'profiles';
    protected $primaryKey = 'id_profile'; 

    protected $fillable = [
        'user_id',
        'name',
        'phone_number',
        'profile_picture', 
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
