<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\LaporanController;

// IMPORT MODEL USER
use App\Models\User;

// IMPORT DB FACADE (UNTUK DEBUGGING)
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});
Route::get('/halo', function () {
    return 'Hello World! Aplikasi kasirku berjalan!';
});

// === RUTE AUTENTIKASI ===

// Arahkan /login ke form login
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');

// Arahkan POST dari form login ke fungsi 'login'
// nama 'login.proses' sesuai dengan action form Anda
Route::post('/login', [LoginController::class, 'login'])->name('login.proses')->middleware('guest');

// Rute untuk logout
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');


// === RUTE YANG TERPROTEKSI ===
// Ini adalah contoh halaman yang hanya bisa dibuka setelah login

Route::get('/dashboard', function () {
    // Cukup tampilkan view-nya. Data user sudah diambil di layout.
    return view('dashboard.dashboard'); 
})->middleware('auth')->name('dashboard'); // Beri nama 'dashboard'

Route::get('/pencarian/rawat-jalan', [KasirController::class, 'rawatJalan'])
     ->name('pencarian.rawat-jalan') // <-- Beri nama agar mudah dipanggil
     ->middleware('auth');

Route::get('/pasien/{norm}/tagihan/{jenis_kasir}', [KasirController::class, 'showTagihanPasien'])
     ->name('kasir.pasien.tagihan')
     ->middleware('auth');

Route::post('/kasir/proses-tagihan', [KasirController::class, 'prosesDanBukaTagihan'])
    ->name('kasir.proses-tagihan')
    ->middleware('auth');

Route::get('/kasir/tagihan-lokal/{id}', [KasirController::class, 'showLokalTagihan'])
     ->name('kasir.tagihan.lokal')
     ->middleware('auth');
     
// Halaman untuk menampilkan form bagi tagihan
Route::get('/kasir/bagi-tagihan/{id}', [KasirController::class, 'showBagiTagihan'])
     ->name('kasir.tagihan.bagi')
     ->middleware('auth');

// Rute untuk MENYIMPAN hasil bagi tagihan
Route::post('/kasir/bagi-tagihan/{id}', [KasirController::class, 'storeBagiTagihan'])
     ->name('kasir.tagihan.bagi.store')
     ->middleware('auth');

Route::post('/kasir/bayar-tagihan/{id}', [KasirController::class, 'storePembayaran'])
     ->name('kasir.bayar-tagihan.store')
     ->middleware('auth');

Route::get('/kuitansi/pasien/{id}/cetak', [KasirController::class, 'cetakKuitansi'])
     ->name('kuitansi.cetak.pasien')
     ->middleware('auth');

// Rute untuk mencetak Kuitansi Asuransi
Route::get('/kuitansi/asuransi/{id}/cetak', [KasirController::class, 'cetakKuitansi'])
     ->name('kuitansi.cetak.asuransi')
     ->middleware('auth');

// Rute untuk me-refresh tagihan Simgos
Route::post('/kasir/refresh-tagihan/{id}', [KasirController::class, 'refreshTagihanSimgos'])
    ->name('kasir.tagihan.refresh')
    ->middleware('auth');

// Rute untuk memproses "Buka Kasir"
Route::post('/kasir/buka-sesi', [KasirController::class, 'bukaSesiKasir'])
    ->name('kasir.sesi.buka')
    ->middleware('auth');
// Rute untuk memproses "Tutup Kasir"
Route::post('/kasir/tutup-sesi', [KasirController::class, 'tutupSesiKasir'])
    ->name('kasir.sesi.tutup')
    ->middleware('auth');

Route::middleware(['auth'])->group(function () {
     // Halaman filter/pencarian laporan (sesuai mockup-mu)
     Route::get('/laporan/penerimaan', [LaporanController::class, 'indexPenerimaan'])
          ->name('laporan.penerimaan.index');

      // Halaman detail/cetak laporan (yang sudah kita rancang mockup-nya)
     Route::get('/laporan/sesi/{id}', [LaporanController::class, 'showLaporanSesi'])
          ->name('laporan.sesi.show');
     
     Route::get('/laporan/sesi/{id}/cetak', [LaporanController::class, 'cetakLaporanSesi'])
          ->name('laporan.sesi.cetak')
          ->middleware('auth');
});