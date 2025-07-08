<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth; 

class UpdatePelangganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        $userId = Auth::guard('api')->id(); 

        return [
            'name'         => 'sometimes|string|max:255', 
            'phone_number' => 'sometimes|string|max:15|unique:profile_pelanggans,phone_number,' . $userId . ',user_id',
            'photo'        => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:10000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.sometimes'        => 'Nama tidak boleh kosong jika disertakan.', 
            'name.string'           => 'Nama harus berupa teks.',
            'name.max'              => 'Nama tidak boleh lebih dari 255 karakter.',
            'phone_number.sometimes' => 'Nomor telepon tidak boleh kosong jika disertakan.',
            'phone_number.string'   => 'Nomor telepon harus berupa teks.',
            'phone_number.max'      => 'Nomor telepon tidak boleh lebih dari 15 karakter.',
            'phone_number.unique'   => 'Nomor telepon ini sudah digunakan oleh profil lain.',
            'photo.sometimes'       => 'Foto profil tidak boleh kosong jika disertakan.',
            'photo.image'           => 'File yang diunggah harus berupa gambar.',
            'photo.mimes'           => 'Foto profil harus dalam format: jpeg, png, jpg, gif, atau svg.',
            'photo.max'             => 'Ukuran foto profil tidak boleh lebih dari 2MB.',
        ];
    }
}
