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
            'pengeluaran_category_id' => 'required|exists:pengeluaran_categories,pengeluaran_category_id',
            'jumlah_pengeluaran' => 'required|numeric|min:0',
            'deskripsi_pengeluaran' => 'nullable|string',
        ];

        if ($this->isMethod('put')) {
            $rules['pengeluaran_category_id'] = 'sometimes|required|exists:pengeluaran_categories,pengeluaran_category_id';
            $rules['jumlah_pengeluaran'] = 'sometimes|required|numeric|min:0';
            $rules['deskripsi_pengeluaran'] = 'sometimes|nullable|string';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'pengeluaran_category_id.required' => 'ID kategori pengeluaran wajib diisi.',
            'pengeluaran_category_id.exists' => 'Kategori pengeluaran tidak ditemukan.',
            'jumlah_pengeluaran.required' => 'Jumlah pengeluaran wajib diisi.',
            'jumlah_pengeluaran.numeric' => 'Jumlah pengeluaran harus berupa angka.',
            'jumlah_pengeluaran.min' => 'Jumlah pengeluaran tidak bisa kurang dari 0.',
            'deskripsi_pengeluaran.string' => 'Deskripsi pengeluaran harus berupa teks.',
        ];
    }
}
