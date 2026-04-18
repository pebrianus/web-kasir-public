<?php

use App\Http\Controllers\FarmasiController;
use App\Http\Controllers\LaporanJasaController;
use App\Http\Controllers\PiutangController;
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
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth', 'kasir.jenis');


// === RUTE YANG TERPROTEKSI ===
// Ini adalah contoh halaman yang hanya bisa dibuka setelah login

Route::get('/dashboard', function () {
    // Cukup tampilkan view-nya. Data user sudah diambil di layout.
    return view('dashboard.dashboard');
})->middleware('auth', 'kasir.jenis')->name('dashboard'); // Beri nama 'dashboard'

Route::get('/pencarian/rawat-jalan', [KasirController::class, 'rawatJalan'])
    ->name('pencarian.rawat-jalan') // <-- Beri nama agar mudah dipanggil
    ->middleware('auth', 'kasir.jenis');

Route::get('/pasien/{norm}/tagihan/{jenis_kasir}', [KasirController::class, 'showTagihanPasien'])
    ->name('kasir.pasien.tagihan')
    ->middleware('auth', 'kasir.jenis');

Route::post('/kasir/proses-tagihan', [KasirController::class, 'prosesDanBukaTagihan'])
    ->name('kasir.proses-tagihan')
    ->middleware('auth', 'kasir.jenis');

Route::get('/kasir/tagihan-lokal/{id}', [KasirController::class, 'showLokalTagihan'])
    ->name('kasir.tagihan.lokal')
    ->middleware('auth', 'kasir.jenis');

// Halaman untuk menampilkan form bagi tagihan
Route::get('/kasir/bagi-tagihan/{id}', [KasirController::class, 'showBagiTagihan'])
    ->name('kasir.tagihan.bagi')
    ->middleware('auth', 'kasir.jenis');

// Rute untuk MENYIMPAN hasil bagi tagihan
Route::post('/kasir/bagi-tagihan/{id}', [KasirController::class, 'storeBagiTagihan'])
    ->name('kasir.tagihan.bagi.store')
    ->middleware('auth', 'kasir.jenis');

// Halaman untuk menampilkan form edit rincian tagihan
Route::get('/kasir/edit-rincian/{id}', [KasirController::class, 'showEditRincian'])
    ->name('kasir.tagihan.rincian.edit')
    ->middleware('auth', 'kasir.jenis');

// Rute untuk MENYIMPAN hasil edit rincian tagihan
Route::put('/kasir/edit-rincian/{id}', [KasirController::class, 'updateRincianTagihan'])
    ->name('kasir.tagihan.rincian.update')
    ->middleware('auth', 'kasir.jenis');

Route::post('/kasir/bayar-tagihan/{id}', [KasirController::class, 'storePembayaran'])
    ->name('kasir.bayar-tagihan.store')
    ->middleware('auth', 'kasir.jenis');

Route::get('/kuitansi/pasien/{id}/cetak', [KasirController::class, 'cetakKuitansi'])
    ->name('kuitansi.cetak.pasien')
    ->middleware('auth', 'kasir.jenis');

// Rute untuk mencetak Kuitansi Asuransi
Route::get('/kuitansi/asuransi/{id}/cetak', [KasirController::class, 'cetakKuitansi'])
    ->name('kuitansi.cetak.asuransi')
    ->middleware('auth', 'kasir.jenis');

// Rute untuk me-refresh tagihan Simgos
Route::post('/kasir/refresh-tagihan/{id}', [KasirController::class, 'refreshTagihanSimgos'])
    ->name('kasir.tagihan.refresh')
    ->middleware('auth', 'kasir.jenis');

// Rute untuk mencetak rincian tagihan asuransi
Route::get('/kasir/rincian/asuransi/cetak/{id}', [KasirController::class, 'cetakRincianAsuransi'])
    ->name('rincian.cetak.asuransi')
    ->middleware('auth', 'kasir.jenis');

    // Rute untuk mencetak rincian tagihan asuransi
Route::get('/kasir/rincian/gabungan/cetak/{id}', [KasirController::class, 'cetakRincianGabungan'])
    ->name('rincian.cetak.gabungan')
    ->middleware('auth', 'kasir.jenis');

// Rute untuk mencetak rincian tagihan pasien
Route::get('/kasir/rincian/pasien/cetak/{id}', [KasirController::class, 'cetakRincianPasien'])
    ->name('rincian.cetak.pasien')
    ->middleware('auth', 'kasir.jenis');

// Rute untuk mencetak rincian tagihan lab
Route::get('/kasir/rincian/lab/cetak/{id}', [KasirController::class, 'cetakRincianLab'])
    ->name('rincian.cetak.lab')
    ->middleware('auth', 'kasir.jenis');

// Rute untuk mencetak resep pasien
Route::get('/cetak-resep/{id}', [KasirController::class, 'cetakResep'])
    ->name('rincian.cetak.resep')
    ->middleware('auth', 'kasir.jenis');

// Rute untuk memproses "Buka Kasir"
Route::post('/kasir/buka-sesi', [KasirController::class, 'bukaSesiKasir'])
    ->name('kasir.sesi.buka')
    ->middleware('auth', 'kasir.jenis');
// Rute untuk memproses "Tutup Kasir"
Route::post('/kasir/tutup-sesi', [KasirController::class, 'tutupSesiKasir'])
    ->name('kasir.sesi.tutup')
    ->middleware('auth', 'kasir.jenis');

Route::middleware(['auth', 'kasir.jenis'])->group(function () {
    // Halaman filter/pencarian laporan (sesuai mockup-mu)
    Route::get('/laporan/penerimaan', [LaporanController::class, 'indexPenerimaan'])
        ->name('laporan.penerimaan.index');

    // Halaman detail/cetak laporan (yang sudah kita rancang mockup-nya)
    Route::get('/laporan/sesi/{id}', [LaporanController::class, 'showLaporanSesi'])
        ->name('laporan.sesi.show');

    Route::get('/laporan/sesi/{id}/cetak', [LaporanController::class, 'cetakLaporanSesi'])
        ->name('laporan.sesi.cetak')
        ->middleware('auth', 'kasir.jenis');

    Route::get('/laporan/jasa', [LaporanJasaController::class, 'indexJasa'])
        ->name('laporan.jasa.index');

    Route::post('/laporan/jasa', [LaporanJasaController::class, 'indexJasa'])
        ->name('laporan.jasa.filter');

    Route::get('/laporan/jasa/cetak', [LaporanJasaController::class, 'cetakLaporanJasa'])
        ->name('laporan.jasa.cetak');

    Route::get('/laporan/jasa-lab', [LaporanJasaController::class, 'indexJasaLab'])
        ->name('laporan.jasa.lab.index');

        Route::post('/laporan/jasa-lab', [LaporanJasaController::class, 'indexJasaLab'])
        ->name('laporan.jasa.lab.filter');

        Route::get('/laporan/jasa-lab/cetak', [LaporanJasaController::class, 'cetakLaporanJasaLab'])
        ->name('laporan.jasa.lab.cetak');

        Route::get('/laporan/jasa-dokter', [LaporanJasaController::class, 'indexJasaDokter'])
            ->name('laporan.jasa.dokter.index');
});

// Route batal tagihan
Route::post('/kasir/tagihan/{id}/batal', [App\Http\Controllers\KasirController::class, 'batalPembayaran'])
    ->name('kasir.bayar.batal');

// Route Kasir Farmasi
Route::get('/farmasi', [FarmasiController::class, 'tagihanFarmasi'])
    ->name('farmasi.index') // <-- Beri nama agar mudah dipanggil
    ->middleware('auth', 'kasir.jenis');

Route::get('/farmasi/{id}', [FarmasiController::class, 'showTagihanFarmasi'])
    ->name('farmasi.show')
    ->middleware('auth', 'kasir.jenis');

Route::get('/farmasi/{id}/cetakKuitansi', [FarmasiController::class, 'cetakKuitansiFarmasi'])
    ->name('farmasi.cetakKuitansi')
    ->middleware('auth', 'kasir.jenis');

Route::post('/farmasi/{id}/bayar', [FarmasiController::class, 'prosesPembayaran'])
    ->name('farmasi.bayar');

Route::post('/farmasi/{id}/batal', [FarmasiController::class, 'batalPembayaranFarmasi'])
    ->name('farmasi.batal');

Route::get('/piutang', [PiutangController::class, 'indexPiutang'])
    ->name('piutang.index')
    ->middleware('auth', 'kasir.jenis');

Route::get('/piutang/{id}', [PiutangController::class, 'piutangDetail'])
    ->name('piutang.detail')
    ->middleware('auth', 'kasir.jenis');

Route::get('/piutang/{id}', [PiutangController::class, 'detailPiutang'])
    ->name('piutang.detail')
    ->middleware('auth', 'kasir.jenis');

Route::patch('/piutang/{id}/bayar', [PiutangController::class, 'bayarPiutang'])
    ->name('piutang.bayar')
    ->middleware('auth', 'kasir.jenis');

Route::patch('/piutang/{id}/hapusbuku', [PiutangController::class, 'hapusBukuPiutang'])
    ->name('piutang.hapusbuku')
    ->middleware('auth', 'kasir.jenis');

Route::patch('/piutang/{id}/charity', [PiutangController::class, 'charityPiutang'])
    ->name('piutang.charity')
    ->middleware('auth', 'kasir.jenis');

Route::patch('/piutang/{id}/batal-lunas', [PiutangController::class, 'batalLunasPiutang'])
    ->name('piutang.batallunas')
    ->middleware('auth', 'kasir.jenis');

Route::patch('/piutang/{id}/batal-charity', [PiutangController::class, 'batalCharity'])
    ->name('piutang.batalcharity')
    ->middleware('auth', 'kasir.jenis');

Route::delete('piutang/pembayaran/{pembayaran}/batal', [PiutangController::class, 'batalPembayaran'])
    ->name('piutang.pembayaran.batal')
    ->middleware('auth', 'kasir.jenis');
