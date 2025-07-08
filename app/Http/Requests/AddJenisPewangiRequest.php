<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddJenisPewangiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama'        => 'required|string|max:255|unique:jenis_pewangi,nama', 
            'deskripsi'   => 'nullable|string|max:500',  
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama pewangi wajib diisi.',
            'nama.unique'   => 'Nama pewangi sudah ada.',
            'deskripsi.max' => 'Deskripsi tidak boleh lebih dari 500 karakter.',
        ];
    }
}
