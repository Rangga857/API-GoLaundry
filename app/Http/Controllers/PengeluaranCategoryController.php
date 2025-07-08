<?php

namespace App\Http\Controllers;

use App\Models\PengeluaranCategory;
use App\Http\Requests\AddPengeluaranCategoryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PengeluaranCategoryController extends Controller
{
    public function addPengeluaranCategory(AddPengeluaranCategoryRequest $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Akses ditolak. Pengguna tidak terautentikasi.'], 403);
        }

        try {
            $category = PengeluaranCategory::create([
                'nama' => $request->nama,
            ]);

            return response()->json([
                'message' => 'Kategori pengeluaran berhasil ditambahkan',
                'status_code' => 201,
                'data' => $category
            ], 201);

        } catch (\Exception $e) {
            Log::error("Error adding pengeluaran category: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getAllPengeluaranCategories()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Akses ditolak. Pengguna tidak terautentikasi.'], 403);
        }

        try {
            $categories = PengeluaranCategory::all();

            return response()->json([
                'message' => 'Daftar kategori pengeluaran berhasil diambil',
                'status_code' => 200,
                'data' => $categories
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error getting all pengeluaran categories: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function updatePengeluaranCategory(Request $request, int $id)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Akses ditolak. Pengguna tidak terautentikasi.'], 403);
        }

        $request->validate([
            'nama' => 'required|string|max:255|unique:pengeluaran_categories,nama,' . $id . ',pengeluaran_category_id',
        ], [
            'nama.required' => 'Nama kategori pengeluaran wajib diisi.',
            'nama.unique' => 'Nama kategori pengeluaran sudah ada.',
        ]);

        try {
            $category = PengeluaranCategory::findOrFail($id);
            $category->nama = $request->nama;
            $category->save();

            return response()->json([
                'message' => 'Kategori pengeluaran berhasil diperbarui',
                'status_code' => 200,
                'data' => $category
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Kategori pengeluaran tidak ditemukan.',
                'status_code' => 404,
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error updating pengeluaran category: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function deletePengeluaranCategory(int $id)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Akses ditolak. Pengguna tidak terautentikasi.'], 403);
        }

        try {
            $category = PengeluaranCategory::findOrFail($id);
            $category->delete();

            return response()->json([
                'message' => 'Kategori pengeluaran berhasil dihapus',
                'status_code' => 200,
                'data' => null
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Kategori pengeluaran tidak ditemukan.',
                'status_code' => 404,
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error deleting pengeluaran category: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}
