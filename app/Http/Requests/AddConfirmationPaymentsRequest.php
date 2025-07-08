<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth; 

class AddConfirmationPaymentsRequest extends FormRequest
{
    public function authorize(): bool
    {
       
        return Auth::check(); 
    }

    public function rules(): array
    {
        return [
            'order_id' => 'required|exists:orders_laundries,id',
            'total_weight' => 'required|numeric|min:0', 
            'total_price' => 'required|numeric|min:0', 
            'keterangan' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'Orders ID harus diisi.',
            'order_id.exists' => 'Pesanan tidak ditemukan.',
            'total_weight.required' => 'Total berat harus diisi.',
            'total_weight.numeric' => 'Total berat harus berupa angka.',
            'total_weight.min' => 'Total berat tidak bisa kurang dari 0.',
            'total_price.required' => 'Total harga harus diisi.',
            'total_price.numeric' => 'Total harga harus berupa angka.',
            'total_price.min' => 'Total harga tidak bisa kurang dari 0.',
        ];
    }
}
