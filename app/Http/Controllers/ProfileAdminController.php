<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use App\Http\Requests\AddAdminRequest;
use App\Models\ProfileAdmin;
use App\Services\GoogleMapsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileAdminController extends Controller
{
    protected $googleMapsService;

    public function __construct(GoogleMapsService $googleMapsService)
    {
        $this->googleMapsService = $googleMapsService;
    }

    public function addProfileAdmin(AddAdminRequest $request)
    {
        try {
            $user = Auth::guard('api')->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized: User not authenticated.',
                    'status_code' => 401,
                    'data' => null
                ], 401);
            }

            if (ProfileAdmin::where('user_id', $user->user_id)->exists()) {
                return response()->json([
                    'message' => 'Profil admin sudah ada',
                    'status_code' => 409,
                    'data' => null
                ], 409);
            }

            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');

            if (is_null($latitude) || is_null($longitude)) {
                Log::info('Koordinat tidak disediakan oleh frontend (ADD), melakukan geocoding di backend untuk admin profile.');
                $coordinates = $this->googleMapsService->geocodeAddress($request->address);
                if (!$coordinates) {
                    return response()->json([
                        'message' => 'Gagal mendapatkan koordinat dari alamat. Pastikan alamat valid.',
                        'status_code' => 400,
                        'data' => null
                    ], 400);
                }
                $latitude = $coordinates['latitude'];
                $longitude = $coordinates['longitude'];
            } else {
                Log::info('Koordinat disediakan oleh frontend (ADD), menggunakan nilai yang diterima.');
            }

            $profile = new ProfileAdmin();
            $profile->user_id = $user->user_id;
            $profile->name = $request->name;
            $profile->address = $request->address;
            $profile->latitude = $latitude;
            $profile->longitude = $longitude;

            $profile->save();

            return response()->json([
                'message' => 'Profil admin berhasil dibuat',
                'status_code' => 201,
                'data' => [
                    'laundry_id' => $profile->laundry_id,
                    'name' => $profile->name,
                    'address' => $profile->address,
                    'latitude' => $profile->latitude,
                    'longitude' => $profile->longitude,
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error("Error adding admin profile: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getProfileAdmin()
    {
        try {
            $user = Auth::guard('api')->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized: User not authenticated.',
                    'status_code' => 401,
                    'data' => null
                ], 401);
            }

            $profile = ProfileAdmin::where('user_id', $user->user_id)->first();

            if (!$profile) {
                return response()->json([
                    'message' => 'Profil admin tidak ditemukan',
                    'status_code' => 404,
                    'data' => null
                ], 404);
            }

            return response()->json([
                'message' => 'Profil admin ditemukan',
                'status_code' => 200,
                'data' => [
                    'laundry_id' => $profile->laundry_id,
                    'name' => $profile->name,
                    'address' => $profile->address,
                    'latitude' => $profile->latitude,
                    'longitude' => $profile->longitude,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error getting admin profile: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function updateProfileAdmin(Request $request)
    {
        try {
            $user = Auth::guard('api')->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized: User not authenticated.',
                    'status_code' => 401,
                    'data' => null
                ], 401);
            }

            $profile = ProfileAdmin::where('user_id', $user->user_id)->first();

            if (!$profile) {
                return response()->json([
                    'message' => 'Profil admin tidak ditemukan',
                    'status_code' => 404,
                    'data' => null
                ], 404);
            }

            Log::info('Request all data for updateProfileAdmin:', $request->all());
            Log::info('Raw Request Body:', ['body' => file_get_contents('php://input')]);

            if ($request->filled('name')) {
                $profile->name = $request->input('name');
                Log::info('Name updated from request:', ['new_name' => $profile->name]);
            } else {
                Log::info('Name not provided or empty in request.');
            }

            $newAddress = $request->input('address');
            $newLatitude = $request->input('latitude');
            $newLongitude = $request->input('longitude');
            if (!is_null($newLatitude) && !is_null($newLongitude)) {
                $profile->latitude = $newLatitude;
                $profile->longitude = $newLongitude;
                Log::info('Koordinat disediakan oleh frontend (UPDATE), menggunakan nilai yang diterima.');
                if ($request->filled('address')) {
                    $profile->address = $newAddress;
                }
            } else if ($request->filled('address') && $newAddress !== $profile->address) {
                Log::info('Koordinat TIDAK disediakan oleh frontend (UPDATE), dan alamat berubah. Melakukan geocoding di backend.');
                $coordinates = $this->googleMapsService->geocodeAddress($newAddress);
                if (!$coordinates) {
                    return response()->json([
                        'message' => 'Gagal mendapatkan koordinat dari alamat baru. Pastikan alamat valid.',
                        'status_code' => 400,
                        'data' => null
                    ], 400);
                }
                $profile->address = $newAddress;
                $profile->latitude = $coordinates['latitude'];
                $profile->longitude = $coordinates['longitude'];
            } else {
                Log::info('Tidak ada perubahan alamat atau koordinat baru yang disediakan untuk update profil admin.');
                if ($request->filled('address')) { 
                    $profile->address = $newAddress;
                }
            }

            Log::info('ProfileAdmin before saving:', $profile->toArray());

            $profile->save();

            return response()->json([
                'message' => 'Profil admin berhasil diperbarui',
                'status_code' => 200,
                'data' => [
                    'laundry_id' => $profile->laundry_id,
                    'name' => $profile->name,
                    'address' => $profile->address,
                    'latitude' => $profile->latitude,
                    'longitude' => $profile->longitude,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error updating admin profile: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}