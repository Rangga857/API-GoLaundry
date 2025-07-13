<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AddPengeluaranRequest extends FormRequest
{

    public function authorize(): bool
    {

        return Auth::check();
    }


    public function rules(): array
    {
        $rules = [
            'nama_kategori' => 'required|string|max:255',
            'jumlah_pengeluaran' => 'required|numeric|min:0',
            'deskripsi_pengeluaran' => 'nullable|string',
        ];

        if ($this->isMethod('put')) {
            $rules['nama_kategori'] = 'sometimes|required|exists:pengeluaran_categories,nama';
            $rules['jumlah_pengeluaran'] = 'sometimes|required|numeric|min:0';
            $rules['deskripsi_pengeluaran'] = 'sometimes|nullable|string';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nama_kategori.required' => 'Nama kategori pengeluaran wajib diisi.',
            'nama_kategori.string' => 'Nama kategori harus berupa teks.',
            'nama_kategori.max' => 'Nama kategori tidak boleh lebih dari :max karakter.',
            'jumlah_pengeluaran.required' => 'Jumlah pengeluaran wajib diisi.',
            'jumlah_pengeluaran.numeric' => 'Jumlah pengeluaran harus berupa angka.',
            'jumlah_pengeluaran.min' => 'Jumlah pengeluaran tidak bisa kurang dari 0.',
            'deskripsi_pengeluaran.string' => 'Deskripsi pengeluaran harus berupa teks.',
        ];
    }
}
