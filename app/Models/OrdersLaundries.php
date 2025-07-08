<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdersLaundries extends Model
{
    use HasFactory;

    protected $table = 'orders_laundries';

    protected $fillable = [
        'id_profile',
        'jenis_pewangi_id',
        'service_id',
        'pickup_address',    
        'pickup_latitude',  
        'pickup_longitude',
        'status',  
    ];

    public function profile()
    {
        return $this->belongsTo(ProfilePelanggan::class, 'id_profile', 'id_profile');
    }

    public function jenisPewangi()
    {
        return $this->belongsTo(JenisPewangi::class, 'jenis_pewangi_id', 'id');
    }

    public function serviceLaundry()
    {
        return $this->belongsTo(ServiceLaundry::class, 'service_id', 'id');
    }

    public function getStatusAttribute($value)
    {
        switch ($value) {
            case 'pending':
                return 'Pending';
            case 'menuju lokasi':
                return 'Menuju Lokasi';
            case 'proses penimbangan':
                return 'Proses Penimbangan';
            case 'proses laundry':
                return 'Proses Laundry';
            case 'proses antar laundry':
                return 'Proses Antar Laundry';
            case 'selesai':
                return 'Selesai';
            default:
                return 'Status Tidak Dikenal';
        }
    }
}
