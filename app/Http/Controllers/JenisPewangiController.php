<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddJenisPewangiRequest;
use App\Models\JenisPewangi;
use Illuminate\Http\Request;

class JenisPewangiController extends Controller
{
    public function addJenisPewangi(AddJenisPewangiRequest $request)
    {
        try {
            $jenisPewangi = JenisPewangi::create([
                'nama'       => $request->nama,
                'deskripsi'  => $request->deskripsi ?? null, 
            ]);

            return response()->json([
                'message'     => 'Jenis pewangi berhasil ditambahkan',
                'status_code' => 201,
                'data'        => [
                    'id'          => $jenisPewangi->id,
                    'nama'        => $jenisPewangi->nama,
                    'deskripsi'   => $jenisPewangi->deskripsi,
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
    public function getAllJenisPewangi()
    {
        try {
            $jenisPewangi = JenisPewangi::all();

            if ($jenisPewangi->isEmpty()) {
                return response()->json([
                    'message'     => 'Jenis pewangi tidak ditemukan',
                    'status_code' => 404,
                    'data'        => null
                ], 404);
            }

            return response()->json([
                'message'     => 'Daftar jenis pewangi ditemukan',
                'status_code' => 200,
                'data'        => $jenisPewangi,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message'     => 'Internal Server Error: ' . $e->getMessage(),
                'status_code' => 500,
                'data'        => null,
            ], 500);
        }
    }
    public function getJenisPewangiById($id)
    {
        try {
            $jenisPewangi = JenisPewangi::find($id);

            if (!$jenisPewangi) {
                return response()->json([
                    'message'     => 'Jenis pewangi tidak ditemukan',
                    'status_code' => 404,
                    'data'        => null
                ], 404);
            }

            return response()->json([
                'message'     => 'Jenis pewangi ditemukan',
                'status_code' => 200,
                'data'        => $jenisPewangi,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message'     => 'Internal Server Error: ' . $e->getMessage(),
                'status_code' => 500,
                'data'        => null,
            ], 500);
        }
    }
    public function updateJenisPewangi(Request $request, $id)
{
    try {
        $jenisPewangi = JenisPewangi::find($id);

        if (!$jenisPewangi) {
            return response()->json([
                'message'     => 'Jenis pewangi tidak ditemukan',
                'status_code' => 404,
                'data'        => null
            ], 404);
        }

        $nama = $request->nama ?? $jenisPewangi->nama;

        $jenisPewangi->update([
            'nama'       => $nama, 
            'deskripsi'  => $request->deskripsi ?? null, 
        ]);

        return response()->json([
            'message'     => 'Jenis pewangi berhasil diperbarui',
            'status_code' => 200,
            'data'        => $jenisPewangi,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message'     => 'Internal Server Error: ' . $e->getMessage(),
            'status_code' => 500,
            'data'        => null,
        ], 500);
    }
}


    public function deleteJenisPewangi($id)
    {
        try {
            $jenisPewangi = JenisPewangi::find($id);

            if (!$jenisPewangi) {
                return response()->json([
                    'message'     => 'Jenis pewangi tidak ditemukan',
                    'status_code' => 404,
                    'data'        => null
                ], 404);
            }

            $jenisPewangi->delete();

            return response()->json([
                'message'     => 'Jenis pewangi berhasil dihapus',
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
}
