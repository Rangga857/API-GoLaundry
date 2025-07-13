<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\ConfirmationPayments;
use App\Models\ProfilePelanggan;
use App\Models\OrdersLaundries;
use App\Http\Requests\AddPembayaranRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PembayaranController extends Controller
{
    public function addPayment(AddPembayaranRequest $request)
    {
        try {
            $user = Auth::user(); 

            $profilePelanggan = ProfilePelanggan::where('user_id', $user->user_id)->firstOrFail();
            $confirmationPayment = ConfirmationPayments::findOrFail($request->confirmation_payment_id);

            if ($confirmationPayment->id_profile !== $profilePelanggan->id_profile) {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses untuk konfirmasi pembayaran ini.',
                    'status_code' => 403,
                    'data' => null
                ], 403);
            }

            $buktiPembayaranPath = null;
            if ($request->hasFile('bukti_pembayaran')) {
                $buktiPembayaranPath = $request->file('bukti_pembayaran')->store('bukti_pembayaran', 'public');
            }

            $pembayaran = Pembayaran::create([
                'confirmation_payment_id' => $request->confirmation_payment_id,
                'metode_pembayaran' => $request->metode_pembayaran,
                'bukti_pembayaran' => $buktiPembayaranPath,
                'status' => 'pending',
            ]);

            return response()->json([
                'message' => 'Pembayaran berhasil ditambahkan. Menunggu konfirmasi admin.',
                'status_code' => 201,
                'data' => [
                    'id' => $pembayaran->id,
                    'confirmation_payment_id' => (int) $pembayaran->confirmation_payment_id, 
                    'metode_pembayaran' => $pembayaran->metode_pembayaran,
                    'bukti_pembayaran_url' => $pembayaran->bukti_pembayaran_url, 
                    'status' => $pembayaran->status,
                    'created_at' => $pembayaran->created_at,
                ]
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Model not found in addPayment: " . $e->getMessage());
            return response()->json([
                'message' => 'Konfirmasi pembayaran atau profil pelanggan tidak ditemukan.',
                'status_code' => 404,
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error adding payment: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function confirmPayment(Request $request, int $id)
    {
        $user = Auth::user();

        $request->validate([
            'status' => 'required|in:confirmed,not confirmed',
        ]);

        try {
            $pembayaran = Pembayaran::findOrFail($id);
            $pembayaran->status = $request->status;
            $pembayaran->save();

            return response()->json([
                'message' => 'Status pembayaran berhasil diperbarui.',
                'status_code' => 200,
                'data' => [
                    'id' => $pembayaran->id,
                    'status' => $pembayaran->status,
                    'confirmation_payment_id' => (int) $pembayaran->confirmation_payment_id, 
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Payment not found in confirmPayment: " . $e->getMessage());
            return response()->json([
                'message' => 'Pembayaran tidak ditemukan.',
                'status_code' => 404,
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error confirming payment: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getPaymentsByPelanggan()
    {
        try {
            $user = Auth::user();
            $profilePelanggan = ProfilePelanggan::where('user_id', $user->user_id)->firstOrFail();

            $payments = Pembayaran::whereHas('confirmationPayment', function ($query) use ($profilePelanggan) {
                $query->where('id_profile', $profilePelanggan->id_profile);
            })
            ->with('confirmationPayment') 
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'confirmation_payment_id' => (int) $payment->confirmation_payment_id, // Cast here too
                    'metode_pembayaran' => $payment->metode_pembayaran,
                    'bukti_pembayaran_url' => $payment->bukti_pembayaran_url,
                    'status' => $payment->status,
                    'total_full_price_order' => (int) $payment->confirmationPayment->total_full_price ?? null, 
                    'keterangan_order' => $payment->confirmationPayment->keterangan ?? null,
                    'created_at' => $payment->created_at,
                    'updated_at' => $payment->updated_at,
                ];
            });

            if ($payments->isEmpty()) {
                return response()->json([
                    'message' => 'Tidak ada catatan pembayaran ditemukan untuk pelanggan ini.',
                    'status_code' => 404,
                    'data' => null
                ], 404);
            }

            return response()->json([
                'message' => 'Catatan pembayaran ditemukan.',
                'status_code' => 200,
                'data' => $payments
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Profile pelanggan not found in getPaymentsByPelanggan: " . $e->getMessage());
            return response()->json([
                'message' => 'Profil pelanggan tidak ditemukan.',
                'status_code' => 404,
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error getting payments by pelanggan: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getAllPayments()
    {
        $user = Auth::user();

        if (!$user || $user->role_id !== 1) {
            Log::warning('Akses ditolak untuk getAllPayments. User ID: ' . ($user ? $user->user_id : 'N/A') . ', Role ID: ' . ($user ? $user->role_id : 'N/A') . '. User role bukan admin (ID 1).');
            return response()->json(['error' => 'Akses ditolak. Hanya admin yang dapat melihat semua pembayaran.'], 403);
        }

        try {
            $payments = Pembayaran::with(['confirmationPayment.profile', 'confirmationPayment.admin', 'confirmationPayment.orders'])
                                ->get()
                                ->map(function ($payment) {
                                    return [
                                        'id' => $payment->id,
                                        'confirmation_payment_id' => (int) $payment->confirmation_payment_id, 
                                        'customer_name' => $payment->confirmationPayment->profile->name,
                                        'metode_pembayaran' => $payment->metode_pembayaran,
                                        'bukti_pembayaran_url' => $payment->bukti_pembayaran_url,
                                        'status' => $payment->status,
                                        'total_full_price_order' => (int) $payment->confirmationPayment->total_full_price ?? 0,
                                        'keterangan_order' => $payment->confirmationPayment->keterangan,
                                        'created_at' => $payment->created_at,
                                        'updated_at' => $payment->updated_at,
                                    ];
                                });

            return response()->json([
                'message' => 'Semua catatan pembayaran ditemukan.',
                'status_code' => 200,
                'data' => $payments
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error getting all payments: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
   public function getPaymentByOrderId(int $orderId) // Menerima orderId
    {
        try {
            $user = Auth::user();

            // 1. Cari Order berdasarkan $orderId
            // 2. Eager load relasi yang dibutuhkan:
            //    - confirmationPayment (dari order)
            //    - pembayaran (dari confirmationPayment)
            //    - profile (dari confirmationPayment)
            //    - admin (dari confirmationPayment)
            //    - serviceLaundry (dari order)
            //    - jenisPewangi (dari order)
            $order = OrdersLaundries::with([
                'confirmationPayment.pembayaran', // Relasi dari OrdersLaundries -> ConfirmationPayments -> Pembayaran
                'confirmationPayment.profile',    // Relasi dari ConfirmationPayments -> ProfilePelanggan
                'confirmationPayment.admin',      // Relasi dari ConfirmationPayments -> User (admin)
                'serviceLaundry',                 // Relasi langsung dari OrdersLaundries -> ServiceLaundry
                'jenisPewangi',                   // Relasi langsung dari OrdersLaundries -> JenisPewangi
            ])->findOrFail($orderId);

            $confirmationPayment = $order->confirmationPayment;

            // Jika order tidak memiliki confirmation payment
            if (!$confirmationPayment) {
                return response()->json([
                    'message' => 'Konfirmasi pembayaran untuk pesanan ini belum dibuat.',
                    'status_code' => 404,
                    'data' => null
                ], 404);
            }

            $pembayaran = $confirmationPayment->pembayaran; // Dapatkan objek Pembayaran dari ConfirmationPayment

            // Jika confirmation payment ada, tetapi pembayaran belum dibuat oleh pelanggan
            if (!$pembayaran) {
                 return response()->json([
                    'message' => 'Pelanggan belum melakukan pembayaran untuk pesanan ini.',
                    'status_code' => 404,
                    'data' => null
                ], 404);
            }

            // Authorization check: Hanya admin atau pelanggan terkait yang bisa melihat.
            // Memastikan id_profile di confirmationPayment sesuai dengan pelanggan yang login.
            if ($user->role_id !== 1) { // Asumsi role_id 1 adalah admin
                $profilePelanggan = ProfilePelanggan::where('user_id', $user->id)->first(); // Gunakan $user->id
                // Pastikan profile pelanggan ditemukan DAN id_profile-nya cocok
                if (!$profilePelanggan || $confirmationPayment->id_profile !== $profilePelanggan->id_profile) {
                    return response()->json([
                        'message' => 'Anda tidak memiliki akses untuk melihat detail pembayaran ini.',
                        'status_code' => 403,
                        'data' => null
                    ], 403);
                }
            }

            $formattedPayment = [
                'id' => $pembayaran->id,
                'confirmation_payment_id' => (int) $confirmationPayment->id, // ID dari tabel confirmation_payments
                'metode_pembayaran' => $pembayaran->metode_pembayaran,
                'bukti_pembayaran_url' => $pembayaran->bukti_pembayaran_url, // Menggunakan accessor
                'status' => $pembayaran->status,
                'total_full_price_order' => (int) ($confirmationPayment->total_full_price ?? 0),
                'keterangan_order' => $confirmationPayment->keterangan,
                'customer_name' => $confirmationPayment->profile->name,
                'admin_name' => $confirmationPayment->admin->name,
                'order_details' => [
                    'order_id' => $order->id,
                    'pickup_address' => $order->pickup_address,
                    'status_order' => $order->status, // Status dari order
                    'total_weight' => (float) ($confirmationPayment->total_weight),
                    'total_ongkir' => (float) ($confirmationPayment->total_ongkir),
                    'total_harga_item_order' => (float) ($order->total_harga_item), // Asumsi ada di OrdersLaundries
                ],
                'created_at' => $pembayaran->created_at,
                'updated_at' => $pembayaran->updated_at,
            ];

            return response()->json([
                'message' => 'Detail pembayaran ditemukan.',
                'status_code' => 200,
                'data' => $formattedPayment
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Order not found or no associated payment/confirmation: " . $e->getMessage());
            return response()->json([
                'message' => 'Pesanan tidak ditemukan.',
                'status_code' => 404,
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error getting payment by order ID: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}
