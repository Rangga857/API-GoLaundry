<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengeluaran extends Model
{
    use HasFactory;

    protected $table = 'pengeluaran'; 

    protected $fillable = [
        'pengeluaran_category_id',
        'jumlah_pengeluaran',
        'deskripsi_pengeluaran',
    ];


    public function category()
    {
        return $this->belongsTo(PengeluaranCategory::class, 'pengeluaran_category_id', 'pengeluaran_category_id');
    }
}
