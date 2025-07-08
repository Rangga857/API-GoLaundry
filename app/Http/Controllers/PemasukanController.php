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

class PemasukanController extends Controller
{
    public function addPemasukan(AddPemasukanRequest $request)
    {

        $user = Auth::user();
        Log::info('Attempting to add offline income. User ID: ' . ($user ? $user->user_id : 'N/A'));

        try {
            DB::beginTransaction();

            $pemasukanData = [
                'amount' => $request->amount,
                'description' => $request->description,
                'transaction_date' => $request->transaction_date,
            ];

            $pemasukan = Pemasukan::create($pemasukanData);

            DB::commit();

            return response()->json([
                'message' => 'Pemasukan offline berhasil ditambahkan.',
                'status_code' => 201,
                'data' => $pemasukan
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
                    'amount' => (float) $item->amount,
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
                    'total_pemasukan_offline_tercatat' => (float) $totalOfflineIncome,
                    'total_pemasukan_online_terkonfirmasi' => (float) $totalConfirmedOnlineIncome,
                    'grand_total_pemasukan' => (float) $grandTotalPemasukan
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
    public function deletePemasukan(int $id)
    {
        $user = Auth::user();

        try {
            $pemasukan = Pemasukan::findOrFail($id);

            $pemasukan->delete();

            return response()->json([
                'message' => 'Pemasukan berhasil dihapus.',
                'status_code' => 200,
                'data' => null
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
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
                'total_pemasukan_offline_tercatat' => (float) $totalOfflineIncome, 
                'total_pemasukan_online_terkonfirmasi' => (float) $totalConfirmedOnlineIncome, 
                'total_pemasukan' => (float) $grandTotalPemasukan,
                'total_pengeluaran' => (float) $totalPengeluaran,
                'total_laba' => (float) $totalLaba,
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
