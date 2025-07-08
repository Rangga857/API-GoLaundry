<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddPelangganRequest;
use App\Http\Requests\UpdatePelangganRequest; // Import Form Request baru
use App\Models\ProfilePelanggan;
use Illuminate\Http\Request; // Masih diperlukan untuk metode yang tidak menggunakan Form Request
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProfilePelangganController extends Controller
{

    public function addProfilePelanggan(AddPelangganRequest $request)
    {
        try {
            $user = Auth::guard('api')->user();
            Log::info('User data for addProfilePelanggan:', ['user' => $user]);

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized: User not authenticated.',
                    'status_code' => 401,
                    'data' => null
                ], 401);
            }

            if (ProfilePelanggan::where('user_id', $user->user_id)->exists()) {
                return response()->json([
                    'message' => 'Profil pelanggan sudah ada untuk pengguna ini.',
                    'status_code' => 409, 
                    'data' => null
                ], 409);
            }

            $profile = new ProfilePelanggan();
            $profile->user_id = $user->user_id;
            $profile->name = $request->name;
            $profile->phone_number = $request->phone_number;

            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $directory = 'photos/' . $user->user_id;
                $profilePicturePath = $photo->store($directory, 'public');
                $profile->profile_picture = $profilePicturePath;
            } else {
                Log::error("AddPelangganRequest passed validation but 'photo' file was missing.");
                return response()->json([
                    'message' => 'Internal Server Error: Foto profil tidak ditemukan setelah validasi.',
                    'status_code' => 500,
                    'data' => null,
                ], 500);
            }

            $profile->save();
            return response()->json([
                'message' => 'Profil pelanggan berhasil dibuat',
                'status_code' => 201, 
                'data' => [
                    'id_profile' => $profile->id_profile,
                    'name' => $profile->name,
                    'phone_number' => $profile->phone_number,
                    'profile_picture' => asset('storage/' . $profile->profile_picture),
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error("Error adding customer profile: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getProfilePelanggan()
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
            $profile = ProfilePelanggan::where('user_id', $user->user_id)->first();

            if (!$profile) {
                return response()->json([
                    'message' => 'Profil pelanggan tidak ditemukan.',
                    'status_code' => 404, 
                    'data' => null
                ], 404);
            }

            $imageUrl = asset('storage/' . $profile->profile_picture);

            return response()->json([
                'message' => 'Profil pelanggan ditemukan',
                'status_code' => 200, // OK
                'data' => [
                    'id_profile' => $profile->id_profile,
                    'name' => $profile->name,
                    'phone_number' => $profile->phone_number,
                    'profile_picture' => $imageUrl,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error getting customer profile: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function updateProfilePelanggan(UpdatePelangganRequest $request)
    {
        try {
            $user = Auth::guard('api')->user();
            Log::info('User data for updateProfilePelanggan:', ['user' => $user]);
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized: User not authenticated.',
                    'status_code' => 401,
                    'data' => null
                ], 401);
            }
            $profile = ProfilePelanggan::where('user_id', $user->user_id)->first();
            if (!$profile) {
                return response()->json([
                    'message' => 'Profil pelanggan tidak ditemukan.',
                    'status_code' => 404, 
                    'data' => null
                ], 404);
            }
            if ($request->has('name')) {
                $profile->name = $request->name;
                Log::info('Updating name to: ' . $request->name);
            }
            if ($request->has('phone_number')) {
                $profile->phone_number = $request->phone_number;
                Log::info('Updating phone_number to: ' . $request->phone_number);
            }
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                Log::info('New photo file detected. Original name: ' . $photo->getClientOriginalName() . ', Size: ' . $photo->getSize() . ' bytes');
                if ($profile->profile_picture) {
                    Log::info('Checking if old profile picture exists: ' . $profile->profile_picture);
                    if (Storage::disk('public')->exists($profile->profile_picture)) {
                        try {
                            Storage::disk('public')->delete($profile->profile_picture);
                            Log::info('Old profile picture deleted successfully: ' . $profile->profile_picture);
                        } catch (\Exception $e) {
                            Log::error('Failed to delete old profile picture: ' . $profile->profile_picture . ' - ' . $e->getMessage());
                        }
                    } else {
                        Log::warning('Old profile picture not found at path: ' . $profile->profile_picture . '. Skipping deletion.');
                    }
                } else {
                    Log::info('No old profile picture path found in database. Skipping deletion.');
                }
                $directory = 'photos/' . $user->user_id;
                try {
                    $profilePicturePath = $photo->store($directory, 'public');
                    $profile->profile_picture = $profilePicturePath;
                    Log::info('New profile picture stored successfully at: ' . $profile->profile_picture);
                } catch (\Exception $e) {
                    Log::error('Failed to store new profile picture: ' . $e->getMessage(), ['exception' => $e]);
                    return response()->json([
                        'status_code' => 500,
                        'message' => 'Internal Server Error: Gagal menyimpan foto profil baru. ' . $e->getMessage(),
                        'data' => null,
                    ], 500);
                }
            } else {

                Log::info('No new photo uploaded. Keeping existing profile picture.');
            }

            $profile->save();
            Log::info('Profile saved successfully. New profile data in DB: ', ['profile' => $profile->toArray()]);

            $profilePictureUrl = asset('storage/' . $profile->profile_picture);
            if ($profile->profile_picture) {
                $timestamp = $profile->updated_at ? $profile->updated_at->timestamp : now()->timestamp;
                $profilePictureUrl .= '?v=' . $timestamp;
            }
            Log::info('Returning profile picture URL with cache-busting: ' . $profilePictureUrl);


            return response()->json([
                'message' => 'Profil pelanggan berhasil diperbarui',
                'status_code' => 200, // OK
                'data' => [
                    'id_profile' => $profile->id_profile,
                    'name' => $profile->name,
                    'phone_number' => $profile->phone_number,
                    'profile_picture' => $profilePictureUrl,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error updating customer profile (general catch): " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getAllProfiles()
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

            $profiles = ProfilePelanggan::with('user')->get()->map(function ($profile) {
                $profilePictureUrl = asset('storage/' . $profile->profile_picture);
                if ($profile->profile_picture) {
                    $timestamp = $profile->updated_at ? $profile->updated_at->timestamp : now()->timestamp;
                    $profilePictureUrl .= '?v=' . $timestamp;
                }

                return [
                    'id_profile' => $profile->id_profile,
                    'user_id' => $profile->user_id,
                    'name' => $profile->name,
                    'phone_number' => $profile->phone_number,
                    'profile_picture' => $profilePictureUrl, // Menggunakan URL dengan cache-busting
                    'user_email' => $profile->user->email ?? null, // Menggunakan null coalescing operator jika user tidak ada
                ];
            });

            // Mengembalikan respons sukses dengan semua data profil.
            return response()->json([
                'message' => 'Semua profil pelanggan ditemukan',
                'status_code' => 200, // OK
                'data' => $profiles
            ], 200);

        } catch (\Exception $e) {
            // Menangkap dan mencatat setiap pengecualian yang terjadi.
            Log::error("Error getting all customer profiles for admin: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}
