<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddPengeluaranCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama'        => 'required|string|max:255|unique:pengeluaran_categories,nama',  
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama kategori pengeluaran wajib diisi.',
            'nama.unique'   => 'Nama kategori pengeluaran sudah ada.',
            'nama.required' => 'Nama kategori pengeluaran wajib diisi.',
            'nama.string' => 'Nama kategori pengeluaran harus berupa teks.',
        ];
    }
}
