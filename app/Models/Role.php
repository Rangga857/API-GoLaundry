<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name'];
    protected $table = 'roles';
    protected $primaryKey = 'role_id';
    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }
   
}
