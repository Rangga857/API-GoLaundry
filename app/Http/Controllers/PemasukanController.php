<?php

namespace App\Http\Controllers;

use App\Models\Pemasukan;
use App\Models\ConfirmationPayments;
use App\Models\Pembayaran;
use App\Models\Pengeluaran;
use App\Http\Requests\AddPemasukanRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\ModelNotFoundException; // Import ini untuk menangani 404

class PemasukanController extends Controller
{
    public function addPemasukan(AddPemasukanRequest $request)
    {
        $user = Auth::user();
        Log::info('Attempting to add offline income. User ID: ' . ($user ? $user->user_id : 'N/A'));

        try {
            DB::beginTransaction();

            $pemasukanData = [
                'amount' => $request->amount, // Request already validated as numeric by AddPemasukanRequest
                'description' => $request->description,
                'transaction_date' => $request->transaction_date,
            ];

            $pemasukan = Pemasukan::create($pemasukanData);

            DB::commit();

            return response()->json([
                'message' => 'Pemasukan offline berhasil ditambahkan.',
                'status_code' => 201,
                'data' => [
                    'id' => $pemasukan->id,
                    'amount' => (float) $pemasukan->amount, // <--- Dipastikan float
                    'description' => $pemasukan->description,
                    'transaction_date' => $pemasukan->transaction_date->format('Y-m-d H:i:s'),
                    'created_at' => $pemasukan->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $pemasukan->updated_at->format('Y-m-d H:i:s'),
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error adding offline income: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getAllPemasukan()
    {
        $user = Auth::user();

        try {
            $pemasukan = Pemasukan::all()->map(function ($item) {
                return [
                    'id' => $item->id,
                    'amount' => (float) $item->amount, // <--- Sudah float
                    'description' => $item->description,
                    'transaction_date' => $item->transaction_date->format('Y-m-d H:i:s'),
                    'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $item->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'message' => 'Semua catatan pemasukan offline ditemukan.',
                'status_code' => 200,
                'data' => $pemasukan
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error getting all offline income: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Update a specific Pemasukan record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePemasukan(Request $request, int $id)
    {
        $user = Auth::user();
        Log::info('Attempting to update income. ID: ' . $id . '. User ID: ' . ($user ? $user->user_id : 'N/A'));

        try {
            DB::beginTransaction();

            $pemasukan = Pemasukan::findOrFail($id);

            // Validate inputs if using base Request. If using PutPemasukanRequest, this step is handled there.
            $validatedData = $request->validate([
                'amount' => 'sometimes|numeric|min:0', // Added validation for 'amount'
                'description' => 'sometimes|string|max:255',
                'transaction_date' => 'sometimes|date',
            ]);

            $pemasukan->update([
                'amount' => $validatedData['amount'] ?? $pemasukan->amount,
                'description' => $validatedData['description'] ?? $pemasukan->description,
                'transaction_date' => $validatedData['transaction_date'] ?? $pemasukan->transaction_date,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Pemasukan berhasil diperbarui.',
                'status_code' => 200,
                'data' => [
                    'id' => $pemasukan->id,
                    'amount' => (float) $pemasukan->amount, // <--- Dipastikan float
                    'description' => $pemasukan->description,
                    'transaction_date' => $pemasukan->transaction_date->format('Y-m-d H:i:s'),
                    'created_at' => $pemasukan->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $pemasukan->updated_at->format('Y-m-d H:i:s'),
                ]
            ], 200);

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            Log::warning("Pemasukan not found for update. ID: " . $id);
            return response()->json([
                'message' => 'Catatan pemasukan tidak ditemukan.',
                'status_code' => 404,
                'data' => null
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) { // Catch validation errors
            DB::rollBack();
            Log::error("Validation error updating income: " . $e->getMessage());
            return response()->json([
                'status_code' => 422,
                'message' => 'Validation Error',
                'errors' => $e->errors(),
                'data' => null,
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating income: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Delete a specific Pemasukan record.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deletePemasukan(int $id)
    {
        $user = Auth::user();
        Log::info('Attempting to delete income. ID: ' . $id . '. User ID: ' . ($user ? $user->user_id : 'N/A'));

        try {
            $pemasukan = Pemasukan::findOrFail($id);

            $pemasukan->delete();

            return response()->json([
                'message' => 'Pemasukan berhasil dihapus.',
                'status_code' => 200,
                'data' => null
            ], 200);

        } catch (ModelNotFoundException $e) {
            Log::warning("Pemasukan not found for deletion. ID: " . $id);
            return response()->json([
                'message' => 'Catatan pemasukan tidak ditemukan.',
                'status_code' => 404,
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error deleting income: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getTotalPemasukan()
    {
        $user = Auth::user();

        try {
            $totalOfflineIncome = Pemasukan::sum('amount');
            $totalConfirmedOnlineIncome = ConfirmationPayments::join('pembayaran', 'confirmation_payments.id', '=', 'pembayaran.confirmation_payment_id')
                                                 ->where('pembayaran.status', 'confirmed')
                                                 ->sum('confirmation_payments.total_full_price');
            $grandTotalPemasukan = $totalOfflineIncome + $totalConfirmedOnlineIncome;

            return response()->json([
                'message' => 'Total pemasukan keseluruhan berhasil dihitung.',
                'status_code' => 200,
                'data' => [
                    'total_pemasukan_offline_tercatat' => (float) $totalOfflineIncome, // <--- Sudah float
                    'total_pemasukan_online_terkonfirmasi' => (float) $totalConfirmedOnlineIncome, // <--- Sudah float
                    'grand_total_pemasukan' => (float) $grandTotalPemasukan // <--- Sudah float
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error getting total income: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function exportPemasukanSummaryPdf()
    {
        $user = Auth::user();
        try {
            $totalOfflineIncome = Pemasukan::sum('amount');

            $totalConfirmedOnlineIncome = ConfirmationPayments::join('pembayaran', 'confirmation_payments.id', '=', 'pembayaran.confirmation_payment_id')
                                                 ->where('pembayaran.status', 'confirmed')
                                                 ->sum('confirmation_payments.total_full_price');

            $grandTotalPemasukan = $totalOfflineIncome + $totalConfirmedOnlineIncome;

            $totalPengeluaran = Pengeluaran::sum('jumlah_pengeluaran');

            $totalLaba = $grandTotalPemasukan - $totalPengeluaran;

            $data = [
                'tanggal' => now()->format('d M Y H:i:s'),
                'total_pemasukan_offline_tercatat' => (float) $totalOfflineIncome, // <--- Sudah float
                'total_pemasukan_online_terkonfirmasi' => (float) $totalConfirmedOnlineIncome, // <--- Sudah float
                'total_pemasukan' => (float) $grandTotalPemasukan, // <--- Sudah float
                'total_pengeluaran' => (float) $totalPengeluaran, // Ini mungkin juga perlu dipastikan float
                'total_laba' => (float) $totalLaba, // <--- Sudah float
            ];

            $pdf = Pdf::loadView('pdf.laporan_keuangan_pemasukan_pdf', $data);

            return $pdf->download('laporan_keuangan_pemasukan_' . now()->format('Ymd_His') . '.pdf');

        } catch (\Exception $e) {
            Log::error("Error exporting income summary to PDF: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}