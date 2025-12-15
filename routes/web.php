<?php


use App\Http\Controllers\AuthController;

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardAdminController;
use App\Http\Controllers\BobotController;
use App\Http\Controllers\DestinasiController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OptimasiController;
use App\Http\Controllers\RouteOptimationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/', [HomeController::class, 'index'])->name('home');



Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});


Route::middleware('auth')->group(function () {
    // Rute untuk semua pengguna yang sudah login
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/rute_optimal', [RouteOptimationController::class, 'index'])->name('rute_optimal');
    // Rute yang hanya bisa diakses oleh admin
    Route::middleware('role:admin')->group(function () {
        Route::get('/destinasi', [DestinasiController::class, 'index'])->name('destinasi.index');
        Route::get('/destinasi/tambah_data', [DestinasiController::class, 'create'])->name('destinasi.create');
        Route::post('/destinasi/tambah_data/store', [DestinasiController::class, 'store'])->name('destinasi.create.store');
        Route::get('/destinasi/{id}/edit', [DestinasiController::class, 'edit'])->name('destinasi.edit');
        Route::put('/destinasi/{id}', [DestinasiController::class, 'update'])->name('destinasi.update');
        Route::delete('/destinasi/{id}', [DestinasiController::class, 'destroy'])->name('destinasi.destroy');


        Route::get('/bobot', [BobotController::class, 'index'])->name('bobot');
        Route::get('/optimasi', [OptimasiController::class, 'index'])->name('optimasi');
        Route::get('/optimasi/show', [OptimasiController::class, 'optimasiShow'])->name('optimasi.show');
        Route::post('/optimasi/store', [OptimasiController::class, 'optimasiStore'])->name('optimasi.store');
        Route::post('/optimasi/destroy', [OptimasiController::class, 'optimasiDestroy'])->name('optimasi.destroy');
        Route::get('/optimasi/generate', [OptimasiController::class, 'generate'])->name('optimasi.generate');
        Route::post('/optimasi/generate/store', [OptimasiController::class, 'store'])->name('optimasi.generate.store');
        // optimasi.destroy
    });
});


