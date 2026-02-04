<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\PmbOtpController;
use App\Http\Controllers\Api\Pmb\PmbXenditPaymentController;
use App\Http\Controllers\Api\Pmb\PmbXenditWebhookController;
use App\Http\Controllers\Api\Pmb\PmbRegisterController;
use App\Http\Controllers\Api\Pmb\PmbYayasanRegisterController;
use App\Http\Controllers\Api\Pmb\PmbKipRegisterController;

use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Pmb\PmbMeController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\Admin\AdminPmbController;


Route::prefix('pmb')->group(function () {
    // OTP
    Route::post('/otp/send',   [PmbOtpController::class, 'send']);
    Route::post('/otp/verify', [PmbOtpController::class, 'verify']);

    // Register
    Route::post('/register', [PmbRegisterController::class, 'store']);
    Route::post('/register/yayasan', [PmbYayasanRegisterController::class, 'store']);
    Route::post('/register/kip', [PmbKipRegisterController::class, 'store']);
});

Route::prefix('pmb/payments/xendit')->group(function () {
    Route::post('/invoice', [PmbXenditPaymentController::class, 'createInvoice']);
    Route::get('/{external_id}', [PmbXenditPaymentController::class, 'show']);
    Route::post('/status', [PmbXenditPaymentController::class, 'status']);

    // webhook
    Route::post('/webhook', [PmbXenditWebhookController::class, 'handle']);
});

// AUTH API - user biasa yang sudah login (butuh session middleware)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/user', [UserController::class, 'me']);
});

// ADMIN API (session-based via web middleware)
Route::prefix('admin')->middleware(['web', 'auth', 'can:admin'])->group(function () {
    Route::get('/dashboard/summary', [AdminDashboardController::class, 'summary']);
    Route::get('/registrations', [AdminDashboardController::class, 'registrations']);
    Route::get('/registrations/all', [\App\Http\Controllers\Api\Admin\RegistrationController::class, 'index']);

    // List gabungan 3 tabel PMB (mandiri + kip + yayasan)
Route::get('/pmb/registrations/all', [\App\Http\Controllers\Api\Admin\PmbRegistrationsController::class, 'index']);

// Detail PMB per source/id (dipakai modal detail/edit)
Route::get('/pmb/{source}/{id}', [\App\Http\Controllers\Api\Admin\PmbController::class, 'show']);


    // user management
    Route::get('/users', [\App\Http\Controllers\Api\Admin\UserController::class, 'index']);
    Route::get('/users/{id}', [\App\Http\Controllers\Api\Admin\UserController::class, 'show']);
    Route::put('/users/{id}', [\App\Http\Controllers\Api\Admin\UserController::class, 'update']);
    Route::delete('/users/{id}', [\App\Http\Controllers\Api\Admin\UserController::class, 'destroy']);
    Route::get('/users-download', [\App\Http\Controllers\Api\Admin\UserController::class, 'download']);

    // Admin: manage PMB records
    // IMPORTANT: untuk upload foto (multipart), izinkan POST juga.
    Route::match(['POST', 'PUT'], '/pmb/{source}/{id}', [\App\Http\Controllers\Api\Admin\PmbController::class, 'update']);
    
    Route::delete('/pmb/{source}/{id}', [\App\Http\Controllers\Api\Admin\PmbController::class, 'destroy']);

    // PDF per user
    Route::get('/users/{id}/pdf', [\App\Http\Controllers\Api\Admin\UserPdfController::class, 'download']);

     Route::get('/users-merged', [AdminUserController::class, 'merged']); // list gabungan (user + pmb)

     Route::delete('/pmb/{source}/{id}', [AdminPmbController::class, 'destroy']);

    Route::get('/users/{user}', [AdminUserController::class, 'show']);
    Route::put('/users/{user}', [AdminUserController::class, 'update']);
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);

});

// PMB USER API (session-based)
Route::prefix('pmb')->middleware(['web', 'pmb.session'])->group(function () {
    Route::get('/me', [PmbMeController::class, 'me']);
      // ✅ Tambahan: ambil data gabungan 3 tabel
    Route::get('/me/merged', [PmbMeController::class, 'merged']);
});


Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/user', [UserController::class, 'me']);
});