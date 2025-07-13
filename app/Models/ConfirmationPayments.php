<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfirmationPayments extends Model
{
    use HasFactory;

    protected $table = 'confirmation_payments';

    protected $fillable = [
        'admin_id',        
        'id_profile',       
        'order_id',       
        'total_weight',
        'total_ongkir',
        'total_price',
        'total_full_price', 
        'keterangan',     
    ];

    public function admin()
    {
        return $this->belongsTo(ProfileAdmin::class, 'admin_id', 'laundry_id'); 
    }

    public function profile()
    {
        return $this->belongsTo(ProfilePelanggan::class, 'id_profile', 'id_profile'); 
    }

    public function orders()
    {
        return $this->belongsTo(OrdersLaundries::class, 'order_id', 'id');
    }

     public function pembayaran()
    {
        return $this->hasOne(Pembayaran::class, 'confirmation_payment_id', 'id');
    }
}
