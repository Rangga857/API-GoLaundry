<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengeluaranCategory extends Model
{
    use HasFactory;

    protected $table = 'pengeluaran_categories';
    protected $primaryKey = 'pengeluaran_category_id';

    protected $fillable = [
        'nama',
    ];
}
