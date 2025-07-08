<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use App\Http\Requests\AddAdminRequest;
use App\Models\ProfileAdmin;
use App\Services\GoogleMapsService; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class ProfileAdminController extends Controller
{
    protected $googleMapsService; 
    private const DEFAULT_PLACEHOLDER_IMAGE_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    public function __construct(GoogleMapsService $googleMapsService)
    {
        $this->googleMapsService = $googleMapsService;
    }
    private function saveBase64Image(string $imageDataBase64, string $prefix = 'profile'): string
    {
        $imageDataBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $imageDataBase64);
        $imageDataBase64 = str_replace(' ', '+', $imageDataBase64);
        $image = base64_decode($imageDataBase64);

        if ($image === false) {
            throw new \Exception("Failed to decode base64 image data.");
        }

        $fileName = $prefix . '_' . time() . '_' . uniqid() . '.png';
        $path = 'photos/' . $fileName;
        Storage::disk('public')->put($path, $image);
        return $path;
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
                Log::info('Koordinat tidak disediakan oleh frontend, melakukan geocoding di backend untuk admin profile.');
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
                Log::info('Koordinat disediakan oleh frontend untuk admin profile, menggunakan nilai yang diterima.');
            }

            $profile = new ProfileAdmin();
            $profile->user_id = $user->user_id; 
            $profile->name = $request->name;
            $profile->address = $request->address;
            $profile->latitude = $latitude;
            $profile->longitude = $longitude; 

            $profilePicturePath = null;
            if ($request->has('profilePicture') && !empty($request->profilePicture)) {
                $profilePicturePath = $this->saveBase64Image($request->profilePicture, 'profile');
            } else {
                $profilePicturePath = $this->saveBase64Image(self::DEFAULT_PLACEHOLDER_IMAGE_BASE64, 'placeholder');
            }
            $profile->profile_picture = $profilePicturePath;

            $profile->save();

            return response()->json([
                'message' => 'Profil admin berhasil dibuat',
                'status_code' => 201,
                'data' => [
                    'laundry_id' => $profile->laundry_id,
                    'name' => $profile->name,
                    'address' => $profile->address,
                    'profile_picture' => asset('storage/' . $profile->profile_picture),
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
            $imageUrl = asset('storage/' . $profile->profile_picture);

            return response()->json([
                'message' => 'Profil admin ditemukan',
                'status_code' => 200,
                'data' => [
                    'laundry_id' => $profile->laundry_id,
                    'name' => $profile->name,
                    'address' => $profile->address,
                    'profile_picture' => $imageUrl,
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

            if ($request->filled('address') && ($newAddress !== $profile->address || (is_null($newLatitude) || is_null($newLongitude)))) { 
                Log::info('Alamat berubah atau koordinat tidak disediakan, melakukan geocoding ulang untuk admin profile.');
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
            } 
            else if (!is_null($newLatitude) && !is_null($newLongitude)) {
                Log::info('Koordinat disediakan manual untuk update admin profile, menggunakan nilai yang diterima.');
                $profile->latitude = $newLatitude;
                $profile->longitude = $newLongitude;
                if ($request->filled('address')) { 
                    $profile->address = $newAddress;
                }
            }

            if ($request->has('profilePicture')) {
                $imageData = $request->profilePicture;
                if (empty($imageData) || $imageData === self::DEFAULT_PLACEHOLDER_IMAGE_BASE64) {
                    if ($profile->profile_picture && 
                        Storage::disk('public')->exists($profile->profile_picture) &&
                        !str_contains($profile->profile_picture, 'placeholder_')) {
                        Storage::disk('public')->delete($profile->profile_picture);
                    }
                    $profile->profile_picture = $this->saveBase64Image(self::DEFAULT_PLACEHOLDER_IMAGE_BASE64, 'placeholder');
                } else {
                    if ($profile->profile_picture && Storage::disk('public')->exists($profile->profile_picture)) {
                        Storage::disk('public')->delete($profile->profile_picture);
                    }
                    $profile->profile_picture = $this->saveBase64Image($imageData, 'profile');
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
                    'profile_picture' => asset('storage/' . $profile->profile_picture), 
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
