<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth; 

class AddOrdersLaundriesRequest extends FormRequest 
{
    public function authorize(): bool
    {
        return Auth::check(); 
    }

    public function rules(): array
    {
        return [
            'jenis_pewangi_name' => 'required|string|exists:jenis_pewangi,nama', 
            'service_name' => 'required|string|exists:services_laundry,title', 
            'pickup_address' => 'required|string|max:255', 
            'pickup_latitude' => 'nullable|numeric', 
            'pickup_longitude' => 'nullable|numeric',
        ];
    }

    public function messages(): array
    {
        return [
            'jenis_pewangi_name.required' => 'Nama jenis pewangi harus diisi.',
            'jenis_pewangi_name.exists' => 'Jenis pewangi tidak ditemukan.', 
            'service_name.required' => 'Nama layanan laundry harus diisi.',
            'service_name.exists' => 'Layanan laundry tidak ditemukan.',   
            'pickup_address.required' => 'Alamat penjemputan harus diisi.',
            'pickup_address.string' => 'Alamat penjemputan harus berupa teks.',
            'pickup_address.max' => 'Alamat penjemputan tidak boleh lebih dari 255 karakter.',
            'pickup_latitude.numeric' => 'Latitude harus berupa angka.',
            'pickup_longitude.numeric' => 'Longitude harus berupa angka.',
        ];
    }
}
