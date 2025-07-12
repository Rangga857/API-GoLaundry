<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    use HasFactory;
    protected $table = 'pembayaran'; 
    protected $fillable = [
        'metode_pembayaran',
        'bukti_pembayaran',
        'status',
        'confirmation_payment_id',
    ];
    public function confirmationPayment()
    {
        return $this->belongsTo(ConfirmationPayments::class, 'confirmation_payment_id', 'id');
    }
    public function getBuktiPembayaranUrlAttribute()
    {
        return $this->bukti_pembayaran ? asset('storage/' . $this->bukti_pembayaran) : null;
    }
}
