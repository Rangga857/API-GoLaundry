<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfilePelangganController;
use App\Http\Controllers\ProfileAdminController;
use App\Http\Controllers\JenisPewangiController;
use App\Http\Controllers\ServiceLaundryController;
use App\Http\Controllers\OrdersLaundriesController; 
use App\Http\Controllers\ConfirmationPaymentsController; 
use App\Http\Controllers\PengeluaranCategoryController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\PemasukanController;
use App\Http\Controllers\CommentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']); 
});

// Grup Route untuk Admin
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    // Profile Admin
    Route::post('admin/profile', [ProfileAdminController::class, 'addProfileAdmin']); 
    Route::get('admin/profile', [ProfileAdminController::class, 'getProfileAdmin']);
    Route::put('admin/profile', [ProfileAdminController::class, 'updateProfileAdmin']);

    // Profile Pelanggan (Admin bisa melihat semua profile pelanggan)
    Route::get('admin/pelanggan', [ProfilePelangganController::class, 'getAllProfiles']);
    
    // Jenis Pewangi
    Route::post('admin/jenispewangi', [JenisPewangiController::class, 'addJenisPewangi']);
    Route::get('admin/jenispewangi', [JenisPewangiController::class, 'getAllJenisPewangi']);
    Route::put('admin/jenispewangi/{id}', [JenisPewangiController::class, 'updateJenisPewangi']);
    Route::delete('admin/jenispewangi/{id}', [JenisPewangiController::class, 'deleteJenisPewangi']);
    
    // Service Laundry
    Route::post('admin/servicelaundry', [ServiceLaundryController::class, 'addServiceLaundry']);
    Route::get('admin/servicelaundry', [ServiceLaundryController::class, 'getAllServiceLaundry']);
    Route::put('admin/servicelaundry/{id}', [ServiceLaundryController::class, 'updateServiceLaundry']);
    Route::delete('admin/servicelaundry/{id}', [ServiceLaundryController::class, 'deleteServiceLaundry']);
    
    // Orders Laundry (Admin)
    Route::get('admin/orders', [OrdersLaundriesController::class, 'getAllOrders']);
    Route::get('admin/orders/{id}', [OrdersLaundriesController::class, 'getSpecificOrderForAdmin']);
    Route::put('admin/orders/{id}/status', [OrdersLaundriesController::class, 'updateOrderStatus']); 

    // Konfirmasi Pembayaran (Admin)
    Route::post('admin/confirmationpayments', [ConfirmationPaymentsController::class, 'store']);
    Route::get('admin/confirmationpayments', [ConfirmationPaymentsController::class, 'getAllConfirmationPayments']);
    Route::get('admin/confirmationpayments/{id}', [ConfirmationPaymentsController::class, 'getConfirmationPaymentByOrderId']);


    // catergory pengeluaran (Admin)
    Route::post('admin/pengeluarancategory', [PengeluaranCategoryController::class, 'addPengeluaranCategory']);
    Route::get('admin/pengeluarancategory', [PengeluaranCategoryController::class, 'getAllPengeluaranCategories']);
    Route::put('admin/pengeluarancategory/{id}', [PengeluaranCategoryController::class, 'updatePengeluaranCategory']);
    Route::delete('admin/pengeluarancategory/{id}', [PengeluaranCategoryController::class, 'deletePengeluaranCategory']);

    //pembayaran
    Route::put('admin/pembayaran/{id}/confirm', [PembayaranController::class, 'confirmPayment']); 
    Route::get('admin/pembayaran', [PembayaranController::class, 'getAllPayments']); 
    Route::get('admin/pembayaran/{id}', [PembayaranController::class, 'getPaymentByOrderId']);

    Route::post('admin/pengeluaran', [PengeluaranController::class, 'addPengeluaran']);
    Route::get('admin/pengeluaran', [PengeluaranController::class, 'getAllPengeluaran']);
    Route::get('admin/pengeluaran/{id}', [PengeluaranController::class, 'getPengeluaranById']);
    Route::put('admin/pengeluaran/{id}', [PengeluaranController::class, 'updatePengeluaran']);
    Route::delete('admin/pengeluaran/{id}', [PengeluaranController::class, 'deletePengeluaran']);

    // Pemasukan (Admin)
    Route::post('admin/pemasukan', [PemasukanController::class, 'addPemasukan']); 
    Route::get('admin/pemasukan', [PemasukanController::class, 'getAllPemasukan']); 
    Route::get('admin/pemasukan/total', [PemasukanController::class, 'getTotalPemasukan']); 
    Route::delete('admin/pemasukan/{id}', [PemasukanController::class, 'deletePemasukan']);
    Route::put('admin/pemasukan/{id}', [PemasukanController::class, 'updatePemasukan']);

    Route::get('admin/laporanharian/pdf', [PemasukanController::class, 'exportPemasukanSummaryPdf']);

     // Komentar (Admin)
    Route::get('admin/comments/{id}', [CommentController::class, 'getCommentByOrderId']); 
});

// Grup Route untuk Pelanggan
Route::middleware(['auth:api', 'role:pelanggan'])->group(function () {
    // Profile Pelanggan
    Route::post('pelanggan/profile', [ProfilePelangganController::class, 'addProfilePelanggan']); 
    Route::get('pelanggan/profile', [ProfilePelangganController::class, 'getProfilePelanggan']);
    Route::put('pelanggan/profile', [ProfilePelangganController::class, 'updateProfilePelanggan']);
    
    // Jenis Pewangi (Pelanggan bisa melihat)
    Route::get('pelanggan/jenispewangi', [JenisPewangiController::class, 'getAllJenisPewangi']);
    Route::get('pelanggan/jenispewangi/{id}', [JenisPewangiController::class, 'getJenisPewangiById']);

    // Service Laundry (Pelanggan bisa melihat)
    Route::get('pelanggan/servicelaundry', [ServiceLaundryController::class, 'getAllServiceLaundry']);
    Route::get('pelanggan/servicelaundry/{id}', [ServiceLaundryController::class, 'getServiceLaundryById']);

    // Orders Laundry (Pelanggan)
    Route::post('pelanggan/orders', [OrdersLaundriesController::class, 'addOrder']); 
    Route::get('pelanggan/myorders', [OrdersLaundriesController::class, 'getOrdersByProfile']); 
    Route::get('pelanggan/myorders/{id}', [OrdersLaundriesController::class, 'getOrdersByProfile']);

    // Konfirmasi Pembayaran (Pelanggan bisa melihat)
    Route::get('/pelanggan/confirmationpayments', [ConfirmationPaymentsController::class, 'getConfirmationPaymentByPelanggan']);
    Route::get('pelanggan/confirmationpayments/{orderId}', [ConfirmationPaymentsController::class, 'getConfirmationPaymentByOrderId']);

    //pembayaran
    Route::post('pelanggan/pembayaran', [PembayaranController::class, 'addPayment']); 
    Route::get('pelanggan/mypembayaran', [PembayaranController::class, 'getPaymentsByPelanggan']);

    // Komentar (Pelanggan)
    Route::post('pelanggan/comments', [CommentController::class, 'addComment']); 
    Route::get('/pelanggan/comments', [CommentController::class, 'getMyComments']);
});
