<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth; 

class AddAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check(); 
    }

    public function rules(): array
    {
        return [
            'name'      => 'required|string|max:255',
            'address'   => 'required|string|max:255',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric', 
        ];
    }

     public function messages(): array
    {
        return [
            'name.required' => 'Nama admin harus diisi.',
            'name.string' => 'Nama admin harus berupa teks.',
            'name.max' => 'Nama admin tidak boleh lebih dari 255 karakter.',
            'address.required' => 'Alamat admin harus diisi.',
            'address.string' => 'Alamat admin harus berupa teks.',
            'address.max' => 'Alamat admin tidak boleh lebih dari 255 karakter.',
            'latitude.numeric' => 'Latitude harus berupa angka.', 
            'longitude.numeric' => 'Longitude harus berupa angka.',
        ];
    }
}
