<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddServiceLaundryRequest;
use App\Models\ServiceLaundry;
use Illuminate\Http\Request;

class ServiceLaundryController extends Controller
{
    public function addServiceLaundry(AddServiceLaundryRequest $request)
    {
        try {
            // Membuat service laundry baru
            $serviceLaundry = ServiceLaundry::create([
                'title'       => $request->title,
                'sub_title'   => $request->subtitle ?? null, 
                'price_per_kg'=> $request->priceperkg,  
            ]);

            return response()->json([
                'message'     => 'Service laundry berhasil ditambahkan',
                'status_code' => 201,
                'data'        => [
                    'id'          => $serviceLaundry->id,
                    'title'       => $serviceLaundry->title,
                    'subtitle'    => $serviceLaundry->sub_title,  
                    'priceperkg'  => $serviceLaundry->price_per_kg,  
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message'     => 'Internal Server Error: ' . $e->getMessage(),
                'status_code' => 500,
                'data'        => null,
            ], 500);
        }
    }
    public function updateServiceLaundry(Request $request, $id)
    {
        try {
            $serviceLaundry = ServiceLaundry::find($id);

            if (!$serviceLaundry) {
                return response()->json([
                    'message'     => 'Service laundry tidak ditemukan',
                    'status_code' => 404,
                    'data'        => null
                ], 404);
            }

            $serviceLaundry->update([
                'title'      => $request->title,
                'sub_title'  => $request->subtitle ?? null, 
                'price_per_kg'=> $request->priceperkg, 
            ]);

            return response()->json([
                'message'     => 'Service laundry berhasil diperbarui',
                'status_code' => 200,
                'data'        => $serviceLaundry,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message'     => 'Internal Server Error: ' . $e->getMessage(),
                'status_code' => 500,
                'data'        => null,
            ], 500);
        }
    }
    public function deleteServiceLaundry($id)
    {
        try {
            $serviceLaundry = ServiceLaundry::find($id);

            if (!$serviceLaundry) {
                return response()->json([
                    'message'     => 'Service laundry tidak ditemukan',
                    'status_code' => 404,
                    'data'        => null
                ], 404);
            }

            $serviceLaundry->delete();

            return response()->json([
                'message'     => 'Service laundry berhasil dihapus',
                'status_code' => 200,
                'data'        => null
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message'     => 'Internal Server Error: ' . $e->getMessage(),
                'status_code' => 500,
                'data'        => null,
            ], 500);
        }
    }
    public function getAllServiceLaundry()
    {
        try {
            $serviceLaundry = ServiceLaundry::all();

            if ($serviceLaundry->isEmpty()) {
                return response()->json([
                    'message'     => 'Service laundry tidak ditemukan',
                    'status_code' => 404,
                    'data'        => null
                ], 404);
            }

            return response()->json([
                'message'     => 'Daftar service laundry ditemukan',
                'status_code' => 200,
                'data'        => $serviceLaundry,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message'     => 'Internal Server Error: ' . $e->getMessage(),
                'status_code' => 500,
                'data'        => null,
            ], 500);
        }
    }

    public function getServiceLaundryById($id)
    {
        try {
            $serviceLaundry = ServiceLaundry::find($id);

            if (!$serviceLaundry) {
                return response()->json([
                    'message'     => 'Service laundry tidak ditemukan',
                    'status_code' => 404,
                    'data'        => null
                ], 404);
            }

            return response()->json([
                'message'     => 'Service laundry ditemukan',
                'status_code' => 200,
                'data'        => $serviceLaundry,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message'     => 'Internal Server Error: ' . $e->getMessage(),
                'status_code' => 500,
                'data'        => null,
            ], 500);
        }
    }
}
