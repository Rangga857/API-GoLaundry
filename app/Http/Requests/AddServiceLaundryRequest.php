<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddServiceLaundryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'title'        => 'required|string|max:255|unique:jenis_pewangi,nama',  
            'subtitle'     => 'nullable|string|max:500', 
            'priceperkg'   => 'required|numeric|min:0',  
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'  => 'Nama pewangi wajib diisi.',
            'title.unique'    => 'Nama pewangi sudah ada.',
            'subtitle.max'    => 'Deskripsi tidak boleh lebih dari 500 karakter.',
            'priceperkg.required' => 'Harga per kg wajib diisi.',
            'priceperkg.numeric'  => 'Harga per kg harus berupa angka.',
            'priceperkg.min'      => 'Harga per kg tidak boleh negatif.',
        ];
    }
}
