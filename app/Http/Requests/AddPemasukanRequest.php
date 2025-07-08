<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AddPemasukanRequest extends FormRequest
{

    public function authorize(): bool
    {
       
        return Auth::check();
    }
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'transaction_date' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Jumlah pemasukan wajib diisi.',
            'amount.numeric' => 'Jumlah pemasukan harus berupa angka.',
            'amount.min' => 'Jumlah pemasukan tidak bisa kurang dari 0.',
            'transaction_date.required' => 'Tanggal transaksi wajib diisi.',
            'transaction_date.date' => 'Tanggal transaksi harus dalam format tanggal yang valid.',
        ];
    }
}
