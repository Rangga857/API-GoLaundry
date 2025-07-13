<?php

namespace App\Http\Controllers;

use App\Models\Pengeluaran;
use App\Http\Requests\AddPengeluaranRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\PengeluaranCategory;

class PengeluaranController extends Controller
{
public function addPengeluaran(AddPengeluaranRequest $request)
    {
        try {
            Auth::user();

            $categoryName = $request->nama_kategori;

            $pengeluaranCategory = PengeluaranCategory::where('nama', $categoryName)->first();

            if (!$pengeluaranCategory) {
                return response()->json([
                    'message' => 'Kategori pengeluaran "' . $categoryName . '" tidak ditemukan.',
                    'status_code' => 404,
                    'data' => null,
                ], 404);
            }
            $pengeluaran = Pengeluaran::create([
                'pengeluaran_category_id' => $pengeluaranCategory->pengeluaran_category_id, 
                'jumlah_pengeluaran' => $request->jumlah_pengeluaran,
                'deskripsi_pengeluaran' => $request->deskripsi_pengeluaran,
            ]);

            return response()->json([
                'message' => 'Pengeluaran berhasil ditambahkan.',
                'status_code' => 201,
                'data' => $pengeluaran
            ], 201);

        } catch (\Exception $e) {
            // Log the detailed error for debugging
            Log::error("Error adding expense: " . $e->getMessage(), [
                'request_data' => $request->all(),
                'exception_trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: Terjadi kesalahan saat menambahkan pengeluaran. Silakan coba lagi nanti.',
                'data' => null,
            ], 500);
        }
    }
    public function getAllPengeluaran()
    {
        try {
            $user = Auth::user();

            $pengeluarans = Pengeluaran::with('category')->get()->map(function ($pengeluaran) {
                return [
                    'id' => $pengeluaran->id,
                    'pengeluaran_category_id' => $pengeluaran->pengeluaran_category_id,
                    'nama' => $pengeluaran->category->nama, 
                    'jumlah_pengeluaran' => (float) $pengeluaran->jumlah_pengeluaran,
                    'deskripsi_pengeluaran' => $pengeluaran->deskripsi_pengeluaran,
                    'created_at' => $pengeluaran->created_at,
                    'updated_at' => $pengeluaran->updated_at,
                ];
            });

            return response()->json([
                'message' => 'Semua catatan pengeluaran ditemukan.',
                'status_code' => 200,
                'data' => $pengeluarans
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error getting all expenses: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getPengeluaranById(int $id)
    {
        try {
            $user = Auth::user();

            $pengeluaran = Pengeluaran::with('category')->findOrFail($id);

            return response()->json([
                'message' => 'Catatan pengeluaran ditemukan.',
                'status_code' => 200,
                'data' => [
                    'id' => $pengeluaran->id,
                    'pengeluaran_category_id' => $pengeluaran->pengeluaran_category_id,
                    'nama' => $pengeluaran->category->nama ?? 'N/A',
                    'jumlah_pengeluaran' => (float) $pengeluaran->jumlah_pengeluaran,
                    'deskripsi_pengeluaran' => $pengeluaran->deskripsi_pengeluaran,
                    'created_at' => $pengeluaran->created_at,
                    'updated_at' => $pengeluaran->updated_at,
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Catatan pengeluaran tidak ditemukan.',
                'status_code' => 404,
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error getting expense by ID: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function updatePengeluaran(AddPengeluaranRequest $request, int $id)
    {
        try {
            $user = Auth::user();

            $pengeluaran = Pengeluaran::findOrFail($id);

            $pengeluaran->fill($request->only(['pengeluaran_category_id', 'jumlah_pengeluaran', 'deskripsi_pengeluaran']));
            $pengeluaran->save();

            $pengeluaran->load('category');

            return response()->json([
                'message' => 'Pengeluaran berhasil diperbarui.',
                'status_code' => 200,
                'data' => [
                    'id' => $pengeluaran->id,
                    'pengeluaran_category_id' => $pengeluaran->pengeluaran_category_id,
                    'nama' => $pengeluaran->category->nama,
                    'jumlah_pengeluaran' => (float) $pengeluaran->jumlah_pengeluaran,
                    'deskripsi_pengeluaran' => $pengeluaran->deskripsi_pengeluaran,
                    'created_at' => $pengeluaran->created_at,
                    'updated_at' => $pengeluaran->updated_at,
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Catatan pengeluaran tidak ditemukan.',
                'status_code' => 404,
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error updating expense: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function deletePengeluaran(int $id)
    {
        try {
            $user = Auth::user();

            $pengeluaran = Pengeluaran::findOrFail($id);
            $pengeluaran->delete();

            return response()->json([
                'message' => 'Pengeluaran berhasil dihapus.',
                'status_code' => 200,
                'data' => null
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Catatan pengeluaran tidak ditemukan.',
                'status_code' => 404,
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error deleting expense: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}
