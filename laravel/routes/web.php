<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PmbLoginController;
use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use App\Models\User;

/* PUBLIC */

Route::get('/', fn() => view('landing'));

/* PMB PUBLIC PAGES */
Route::view('/pmb/register', 'pmb.register');
Route::view('/pmb/register/kip', 'pmb.register');
Route::view('/pmb/register/yayasan', 'pmb.register');

/* AUTH */
Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [PmbLoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [PmbLoginController::class, 'destroy'])->name('logout');

    // landing dashboard untuk semua yang login
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])
        ->middleware('can:admin')
        ->name('admin.dashboard');

    Route::get('/admin/users', function () {
        return view('admin.admin');
    })->middleware('can:admin')->name('admin.users');

    Route::get('/pmb/dashboard', [DashboardController::class, 'pmb'])
        ->middleware('pmb.session')
        ->name('pmb.dashboard');
});

// Development helper: set PMB session quickly for local testing.
// This route is intentionally only available when app is local or debug mode.
if (app()->isLocal() || config('app.debug')) {
    Route::get('/dev/set-pmb/{source}/{id}', function (Request $request, $source, $id) {
        $allowed = ['mandiri', 'yayasan', 'kip'];
        if (!in_array($source, $allowed)) {
            return response('Invalid source', 422);
        }
        session(['pmb_source' => $source, 'pmb_id' => (int)$id]);
        return response()->json(['message' => 'PMB session set', 'source' => $source, 'id' => (int)$id]);
    });

    // Make a user admin (development only)
    Route::get('/dev/make-admin/{email}', function (Request $request, $email) {
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $user->role = 'admin';
        $user->is_admin = true;
        $user->save();
        return response()->json(['message' => 'User promoted to admin', 'email' => $email]);
    });

    // Clear admin flag (development only)
    Route::get('/dev/clear-admin/{email}', function (Request $request, $email) {
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $user->role = null;
        $user->is_admin = false;
        $user->save();
        return response()->json(['message' => 'User admin cleared', 'email' => $email]);
    });
}
