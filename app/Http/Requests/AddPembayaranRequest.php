<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\ProfilePelanggan; 
use App\Models\ConfirmationPayments; 

class AddPembayaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'confirmation_payment_id' => [
                'required',
                'exists:confirmation_payments,id', 
                function ($attribute, $value, $fail) {
                    $user = Auth::user();
                    $profilePelanggan = ProfilePelanggan::where('user_id', $user->user_id)->first();

                    if (!$profilePelanggan) {
                        $fail('Profil pelanggan tidak ditemukan.');
                        return;
                    }

                    $confirmationPayment = ConfirmationPayments::find($value);
                    if (!$confirmationPayment || $confirmationPayment->id_profile !== $profilePelanggan->id_profile) {
                        $fail('Konfirmasi pembayaran tidak valid atau bukan milik Anda.');
                    }
                },
            ],
            'metode_pembayaran' => 'required|in:cash,bank transfer',
            'bukti_pembayaran' => 'required_if:metode_pembayaran,bank transfer|nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'confirmation_payment_id.required' => 'ID konfirmasi pembayaran wajib diisi.',
            'confirmation_payment_id.exists' => 'Konfirmasi pembayaran tidak ditemukan.',
            'metode_pembayaran.required' => 'Metode pembayaran wajib diisi.',
            'metode_pembayaran.in' => 'Metode pembayaran tidak valid.',
            'bukti_pembayaran.required_if' => 'Bukti pembayaran wajib diunggah untuk metode transfer bank.', 
            'bukti_pembayaran.image' => 'Bukti pembayaran harus berupa gambar.',
            'bukti_pembayaran.mimes' => 'Format gambar yang diizinkan untuk bukti pembayaran: jpeg, png, jpg, gif, svg.',
            'bukti_pembayaran.max' => 'Ukuran bukti pembayaran maksimal 2MB.',
        ];
    }

}
