<?php

namespace App\Http\Controllers;

use App\Models\OrdersLaundries;
use App\Models\JenisPewangi;
use App\Models\ServiceLaundry;
use App\Models\ProfilePelanggan;
use App\Http\Requests\AddOrdersLaundriesRequest;
use App\Services\GoogleMapsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrdersLaundriesController extends Controller
{
    protected $googleMapsService;

    public function __construct(GoogleMapsService $googleMapsService)
    {
        $this->googleMapsService = $googleMapsService;
    }
    public function getAllOrders()
    {
        $user = Auth::user();
        if (!$user || $user->role_id !== 1) {
            Log::warning('Access denied for getAllOrders. User role is not admin.');
            return response()->json(['error' => 'Akses ditolak. Hanya admin yang dapat melihat semua pesanan.'], 403);
        }

        try {
            $orders = OrdersLaundries::with(['profile', 'jenisPewangi', 'serviceLaundry'])
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'profile_name' => $order->profile->name,
                        'pickup_address' => $order->pickup_address,
                        'jenis_pewangi_name' => $order->jenisPewangi->nama,
                        'service_title' => $order->serviceLaundry->title,
                        'status' => $order->status,
                        'pickup_latitude' => (float) $order->pickup_latitude,
                        'pickup_longitude' => (float) $order->pickup_longitude,
                    ];
                });

            return response()->json(['orders' => $orders]);
        } catch (\Exception $e) {
            Log::error("Error getting all orders: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

      public function addOrder(AddOrdersLaundriesRequest $request)
    {
        try {
            $user = Auth::user();
            $profilePelanggan = ProfilePelanggan::where('user_id', $user->user_id)->first();
            if (!$profilePelanggan) {
                return response()->json(['error' => 'Profile pelanggan tidak ditemukan. Harap buat profil terlebih dahulu.'], 404);
            }
            $incompleteOrdersCount = OrdersLaundries::where('id_profile', $profilePelanggan->id_profile)
                                                 ->where('status', '!=', 'selesai')
                                                 ->count();

            if ($incompleteOrdersCount > 0) {
                Log::warning('User ' . $user->id . ' attempted to create a new order but has incomplete orders.');
                return response()->json([
                    'error' => 'Anda tidak dapat membuat pesanan baru karena masih ada pesanan sebelumnya yang belum selesai.'
                ], 403);
            }

            $jenisPewangi = JenisPewangi::where('nama', $request->jenis_pewangi_name)->first();
            if (!$jenisPewangi) {
                return response()->json(['error' => 'Jenis Pewangi tidak ditemukan'], 404);
            }

            $serviceLaundry = ServiceLaundry::where('title', $request->service_name)->first();
            if (!$serviceLaundry) {
                return response()->json(['error' => 'Layanan Laundry tidak ditemukan'], 404);
            }

            $latitude = (float) $request->input('pickup_latitude');
            $longitude = (float) $request->input('pickup_longitude');

            if (is_null($latitude) || is_null($longitude) || $latitude === 0.0 || $longitude === 0.0) {
                Log::info('Koordinat penjemputan tidak disediakan oleh frontend atau nol, melakukan geocoding di backend.');
                $coordinates = $this->googleMapsService->geocodeAddress($request->pickup_address);
                if (!$coordinates) {
                    return response()->json([
                        'error' => 'Gagal mendapatkan koordinat dari alamat penjemputan. Pastikan alamat valid.'
                    ], 400);
                }
                $latitude = (float) $coordinates['latitude'];
                $longitude = (float) $coordinates['longitude'];
            } else {
                Log::info('Koordinat penjemputan disediakan oleh frontend, menggunakan nilai yang diterima.');
            }

            $order = OrdersLaundries::create([
                'id_profile' => $profilePelanggan->id_profile,
                'jenis_pewangi_id' => $jenisPewangi->id,
                'service_id' => $serviceLaundry->id,
                'pickup_address' => $request->pickup_address,
                'pickup_latitude' => $latitude,
                'pickup_longitude' => $longitude,
                'status' => 'pending',
            ]);

            // Untuk respons, kita bisa memuat ulang relasi atau langsung menggunakan objek yang sudah ada
            // Dengan with(['jenisPewangi', 'serviceLaundry']) di bawah, kita memastikan relasi sudah dimuat
            // sebelum mengakses properti 'nama' dan 'title'.
            $order->load(['jenisPewangi', 'serviceLaundry']);

            return response()->json([
                'success' => 'Pesanan berhasil dibuat',
                'order' => [
                    'id' => $order->id,
                    'profile_name' => $order->profile->name, // Perlu relasi 'profile' juga dimuat jika ingin ini
                    'pickup_address' => $order->pickup_address,
                    'jenis_pewangi_name' => $order->jenisPewangi->nama, // Menggunakan nama
                    'service_title' => $order->serviceLaundry->title,   // Menggunakan title
                    'status' => $order->status,
                    'pickup_latitude' => (float) $order->pickup_latitude,
                    'pickup_longitude' => (float) $order->pickup_longitude,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error("Error adding order: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->role_id !== 1) {
            return response()->json(['error' => 'Akses ditolak. Hanya admin yang dapat memperbarui status pesanan.'], 403);
        }
        $request->validate([
            'status' => 'required|string|in:pending,menuju lokasi,proses penimbangan,proses laundry,proses antar laundry,selesai',
        ]);

        try {
            $order = OrdersLaundries::findOrFail($id);

            $order->status = $request->status;
            $order->save();

            return response()->json([
                'success' => 'Status pesanan berhasil diperbarui',
                'order' => [
                    'id' => $order->id,
                    'id_profile' => $order->id_profile,
                    'jenis_pewangi_id' => $order->jenis_pewangi_id,
                    'service_id' => $order->service_id,
                    'pickup_address' => $order->pickup_address,
                    'pickup_latitude' => (float) $order->pickup_latitude,
                    'pickup_longitude' => (float) $order->pickup_longitude,
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Pesanan tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error("Error updating order status: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }


    public function getOrdersByProfile(Request $request, $id = null)
    {
        $user = Auth::user();
        // Find the customer profile associated with the authenticated user
        $profilePelanggan = ProfilePelanggan::where('user_id', $user->user_id)->first();

        if (!$profilePelanggan) {
            return response()->json(['error' => 'Profile pelanggan tidak ditemukan'], 404);
        }

        try {
            if ($id) {
                // Retrieve a specific order for the authenticated user's profile
                $order = OrdersLaundries::with(['profile', 'jenisPewangi', 'serviceLaundry'])
                                        ->where('id_profile', $profilePelanggan->id_profile)
                                        ->findOrFail($id);

                // Map the single order data, ensuring numeric fields are cast to float
                $mappedOrder = [
                    'id' => $order->id,
                    'profile_name' => $order->profile->name,
                    'pickup_address' => $order->pickup_address,
                    'jenis_pewangi_name' => $order->jenisPewangi->nama,
                    'service_title' => $order->serviceLaundry->title,
                    'status' => $order->status,
                    'pickup_latitude' => (float) $order->pickup_latitude,
                    'pickup_longitude' => (float) $order->pickup_longitude,
                ];

                return response()->json(['order' => $mappedOrder]);
            } else {
                // Retrieve all orders for the authenticated user's profile
                $orders = OrdersLaundries::with(['profile', 'jenisPewangi', 'serviceLaundry'])
                                        ->where('id_profile', $profilePelanggan->id_profile)
                                        ->get()
                                        ->map(function ($order) {
                                            // Map each order data, ensuring numeric fields are cast to float
                                            return [
                                                'id' => $order->id,
                                                'profile_name' => $order->profile->name,
                                                'pickup_address' => $order->pickup_address,
                                                'jenis_pewangi_name' => $order->jenisPewangi->nama,
                                                'service_title' => $order->serviceLaundry->title,
                                                'status' => $order->status,
                                                'pickup_latitude' => (float) $order->pickup_latitude,
                                                'pickup_longitude' => (float) $order->pickup_longitude,
                                            ];
                                        });

                return response()->json(['orders' => $orders]);
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Pesanan tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error("Error getting orders by profile: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getSpecificOrderForAdmin(int $id)
    {
        $user = Auth::user();

        try {
            // Find the specific order with its relationships
            $order = OrdersLaundries::with(['profile', 'jenisPewangi', 'serviceLaundry'])
                                    ->findOrFail($id);

            // Map the order data, ensuring numeric fields are cast to float
            $mappedOrder = [
                'id' => $order->id,
                'profile_name' => $order->profile->name,
                'pickup_address' => $order->pickup_address,
                'jenis_pewangi_name' => $order->jenisPewangi->nama,
                'service_title' => $order->serviceLaundry->title,
                'status' => $order->status,
                'pickup_latitude' => (float) $order->pickup_latitude,
                'pickup_longitude' => (float) $order->pickup_longitude,
            ];

            return response()->json(['order' => $mappedOrder]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Pesanan tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error("Error getting specific order for admin: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}
