<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisPewangi extends Model
{
    use HasFactory;

    protected $table = 'jenis_pewangi';

    protected $fillable = [
        'nama',
        'deskripsi',
    ];
}
