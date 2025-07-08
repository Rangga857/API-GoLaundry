<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddPelangganRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => 'required|string|max:255',
            'phone_number' => 'required|string|max:15',
            'photo'        => 'required|image|mimes:jpeg,png,jpg,gif|max:10000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'         => 'Nama wajib diisi.',
            'name.string'           => 'Nama harus berupa teks.',
            'name.max'              => 'Nama tidak boleh lebih dari 255 karakter.',
            'phone_number.required' => 'Nomor telepon wajib diisi.',
            'phone_number.string'   => 'Nomor telepon harus berupa teks.',
            'phone_number.max'      => 'Nomor telepon tidak boleh lebih dari 15 karakter.',
            'photo.required'        => 'Foto profil wajib diisi.',
            'photo.image'           => 'File yang diunggah harus berupa gambar.',
            'photo.mimes'           => 'Foto profil harus dalam format: jpeg, png, jpg, atau gif.',
            'photo.max'             => 'Ukuran foto profil tidak boleh lebih dari 2MB.',
        ];
    }
}
