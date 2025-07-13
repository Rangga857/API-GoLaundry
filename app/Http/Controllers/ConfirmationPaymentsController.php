<?php

namespace App\Http\Controllers;

use App\Models\ConfirmationPayments;
use App\Models\OrdersLaundries;
use App\Models\ProfilePelanggan;
use App\Models\ProfileAdmin;
use App\Services\GoogleMapsService;
use App\Services\DistanceCalculatorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Requests\AddConfirmationPaymentsRequest;
use Illuminate\Support\Facades\Log;

class ConfirmationPaymentsController extends Controller
{
    protected $googleMapsService;
    protected $distanceCalculatorService;

    public function __construct(GoogleMapsService $googleMapsService, DistanceCalculatorService $distanceCalculatorService)
    {
        $this->googleMapsService = $googleMapsService;
        $this->distanceCalculatorService = $distanceCalculatorService;
    }

    public function store(AddConfirmationPaymentsRequest $request)
    {
        try {
            $adminUser = Auth::guard('api')->user();
            if (!$adminUser || $adminUser->role_id !== 1) {
                return response()->json(['error' => 'Akses ditolak. Hanya admin yang dapat menambahkan konfirmasi pembayaran.'], 403);
            }

            $profileAdmin = ProfileAdmin::where('user_id', $adminUser->user_id)->first();
            if (!$profileAdmin) {
                return response()->json(['error' => 'Profile admin (lokasi laundry) tidak ditemukan. Harap lengkapi profil admin.'], 404);
            }
            $admin_id_for_db = $profileAdmin->laundry_id;

            $order = OrdersLaundries::with('profile')->findOrFail($request->order_id);

            $pickup_latitude = $order->pickup_latitude;
            $pickup_longitude = $order->pickup_longitude;

            if (is_null($pickup_latitude) || is_null($pickup_longitude)) {
                return response()->json(['error' => 'Koordinat penjemputan untuk pesanan ini tidak ditemukan. Harap pastikan alamat penjemputan sudah di-geocode saat order dibuat.'], 400);
            }

            $distance_meters = $this->distanceCalculatorService->calculateHaversineDistance(
                $profileAdmin->latitude,
                $profileAdmin->longitude,
                $pickup_latitude,
                $pickup_longitude
            );

            $rounded_distance = round($distance_meters / 100) * 100;
            $total_ongkir = ($rounded_distance / 100) * 1000;

            $total_full_price = $request->total_price + $total_ongkir;

            $confirmationPayment = ConfirmationPayments::create([
                'admin_id' => $admin_id_for_db,
                'id_profile' => $order->id_profile,
                'order_id' => $request->order_id,
                'total_weight' => $request->total_weight,
                'total_ongkir' => $total_ongkir,
                'total_price' => $request->total_price,
                'total_full_price' => $total_full_price,
                'keterangan' => $request->keterangan,
            ]);

            return response()->json(['success' => 'Konfirmasi pembayaran berhasil ditambahkan', 'confirmation_payment' => $confirmationPayment], 201);
        } catch (\Exception $e) {
            \Log::error("Error adding confirmation payment: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getConfirmationPaymentByPelanggan()
    {
        try {
            $pelangganUser = Auth::guard('api')->user();
            $profilePelanggan = ProfilePelanggan::where('user_id', $pelangganUser->user_id)->first();

            if (!$profilePelanggan) {
                return response()->json([
                    'message' => 'Profile pelanggan tidak ditemukan.',
                    'status_code' => 404,
                    'data' => null
                ], 404);
            }
            $id_profile = $profilePelanggan->id_profile;

            $confirmationPayments = ConfirmationPayments::where('id_profile', $id_profile)
                                         ->with(['admin', 'profile', 'orders'])
                                         ->get()
                                         ->map(function ($payment) {
                                             return [
                                                 'id' => $payment->id,
                                                 'order_id' => $payment->order_id,
                                                 'customer_name' => $payment->profile->name,
                                                 'laundry_name' => $payment->admin->name,
                                                 'pickup_address' => $payment->orders->pickup_address,
                                                 'total_weight' => (float) $payment->total_weight,
                                                 'total_price' => (float) $payment->total_price,
                                                 'total_ongkir' => (float) $payment->total_ongkir,
                                                 'total_full_price' => (float) $payment->total_full_price,
                                                 'keterangan' => $payment->keterangan,
                                                 'created_at' => $payment->created_at,
                                                 'updated_at' => $payment->updated_at,
                                             ];
                                         });

            if ($confirmationPayments->isEmpty()) {
                return response()->json([
                    'message' => 'Konfirmasi pembayaran tidak ditemukan untuk pelanggan ini.',
                    'status_code' => 404,
                    'data' => [] // Mengubah data menjadi array kosong untuk konsistensi
                ], 404);
            }

            return response()->json([
                'message' => 'Konfirmasi pembayaran ditemukan.',
                'status_code' => 200,
                'data' => $confirmationPayments
            ], 200);
        } catch (\Exception $e) {
            \Log::error("Error getting confirmation payments by customer: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getAllConfirmationPayments()
    {
        try {
            $user = Auth::user();
            if (!$user || $user->role_id !== 1) {
                \Log::warning('Access denied for getAllConfirmationPayments. User ID: ' . ($user ? $user->user_id : 'N/A') . ', Role ID: ' . ($user ? $user->role_id : 'N/A') . '. User role is not admin (ID 1).');
                return response()->json(['error' => 'Akses ditolak. Hanya admin yang dapat melihat semua konfirmasi pembayaran.'], 403);
            }

            $confirmationPayments = ConfirmationPayments::with(['admin', 'profile', 'orders'])
                                         ->get()
                                         ->map(function ($payment) {
                                             return [
                                                 'id' => $payment->id,
                                                 'order_id' => $payment->order_id,
                                                 'customer_name' => $payment->profile->name,
                                                 'laundry_name' => $payment->admin->name,
                                                 'pickup_address' => $payment->orders->pickup_address,
                                                 'total_weight' => (float) $payment->total_weight,
                                                 'total_price' => (float) $payment->total_price,
                                                 'total_ongkir' => (float) $payment->total_ongkir,
                                                 'total_full_price' => (float) $payment->total_full_price,
                                                 'keterangan' => $payment->keterangan,
                                                 'created_at' => $payment->created_at,
                                                 'updated_at' => $payment->updated_at,
                                             ];
                                         });

            return response()->json([
                'message' => 'Semua konfirmasi pembayaran.',
                'status_code' => 200,
                'data' => $confirmationPayments
            ], 200);
        } catch (\Exception $e) {
            \Log::error("Error getting all confirmation payments: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getConfirmationPaymentByOrderId(int $orderId)
    {
        try {
            $user = Auth::guard('api')->user();

            if (!$user) {
                return response()->json(['error' => 'Autentikasi diperlukan.'], 401);
            }

            // Temukan konfirmasi pembayaran berdasarkan orderId
            $confirmationPayment = ConfirmationPayments::where('order_id', $orderId)
                                                      ->with(['admin', 'profile', 'orders'])
                                                      ->first();

            if (!$confirmationPayment) {
                return response()->json([
                    'message' => 'Konfirmasi pembayaran untuk Order ID ' . $orderId . ' tidak ditemukan.',
                    'status_code' => 404,
                    'data' => null
                ], 404);
            }

            // Verifikasi apakah pengguna memiliki hak akses untuk melihat konfirmasi pembayaran ini
            if ($user->role_id === 2) { // Jika pengguna adalah pelanggan
                $profilePelanggan = ProfilePelanggan::where('user_id', $user->user_id)->first();
                if (!$profilePelanggan || $confirmationPayment->id_profile !== $profilePelanggan->id_profile) {
                    return response()->json(['error' => 'Akses ditolak. Anda tidak memiliki izin untuk melihat konfirmasi pembayaran ini.'], 403);
                }
            } elseif ($user->role_id === 1) { // Jika pengguna adalah admin
                $profileAdmin = ProfileAdmin::where('user_id', $user->user_id)->first();
                if (!$profileAdmin || $confirmationPayment->admin_id !== $profileAdmin->laundry_id) {
                     return response()->json(['error' => 'Akses ditolak. Konfirmasi pembayaran ini bukan milik laundry Anda.'], 403);
                }
            } else { // Role lain yang tidak diizinkan
                 return response()->json(['error' => 'Akses ditolak. Peran pengguna tidak diizinkan.'], 403);
            }

            // Map data ke format yang konsisten dengan response lain
            $mappedPayment = [
                'id' => $confirmationPayment->id,
                'order_id' => $confirmationPayment->order_id,
                'customer_name' => $confirmationPayment->profile->name,
                'laundry_name' => $confirmationPayment->admin->name,
                'pickup_address' => $confirmationPayment->orders->pickup_address,
                'total_weight' => (float) $confirmationPayment->total_weight,
                'total_price' => (float) $confirmationPayment->total_price,
                'total_ongkir' => (float) $confirmationPayment->total_ongkir,
                'total_full_price' => (float) $confirmationPayment->total_full_price,
                'keterangan' => $confirmationPayment->keterangan,
                'created_at' => $confirmationPayment->created_at,
                'updated_at' => $confirmationPayment->updated_at,
            ];

            return response()->json([
                'message' => 'Konfirmasi pembayaran ditemukan.',
                'status_code' => 200,
                'data' => $mappedPayment
            ], 200);
        } catch (\Exception $e) {
            \Log::error("Error getting confirmation payment by order ID: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}