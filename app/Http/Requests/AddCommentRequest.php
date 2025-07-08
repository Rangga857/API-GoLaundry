<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AddCommentRequest extends FormRequest
{
   
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->role_id === 2; 
    }

    public function rules(): array
    {
        return [
            'order_id' => 'required|integer|exists:orders_laundries,id', 
            'comment_text' => 'required|string|max:1000', 
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'ID pesanan wajib diisi.',
            'order_id.integer' => 'ID pesanan harus berupa angka.',
            'order_id.exists' => 'Pesanan tidak ditemukan.',
            'comment_text.required' => 'Komentar wajib diisi.',
            'comment_text.string' => 'Komentar harus berupa teks.',
            'comment_text.max' => 'Komentar tidak boleh lebih dari 1000 karakter.',
        ];
    }
}
