<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KasirSesi;
use App\Models\KasirTagihanHead;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PDF;

class LaporanController extends Controller
{
    /**
     * Menampilkan halaman filter Laporan Penerimaan (sesuai mockup).
     */
    public function indexPenerimaan(Request $request)
    {
        // Ambil query dasar: semua sesi yang sudah TUTUP
        $query = KasirSesi::where('status', 'TUTUP')
                          ->orderBy('waktu_tutup', 'desc');

        // --- Logika Filter Tanggal (dari mockup-mu) ---
        $tanggalInput = $request->input('tanggal');

                // Terima token jenis kasir (1=RJ, 2=IGD, 3=RI)
        $jenis_kasir = $request->input('jenis');

        if ($tanggalInput) {
            // Jika kasir menginput tanggal, filter berdasarkan tanggal itu
            $tanggal = Carbon::parse($tanggalInput);
            $query->whereDate('waktu_buka', $tanggal);
        } else {
            // Default: tampilkan laporan hari ini saja
            $query->whereDate('waktu_buka', Carbon::today());
            $tanggalInput = Carbon::today()->format('Y-m-d'); // Set untuk ditampilkan di input
        }
        // --- Akhir Logika Filter ---

        $daftarSesi = $query->paginate(20);

        return view('laporan.index-penerimaan', [ // <-- UBAH FOLDERNYA
            'daftarSesi' => $daftarSesi,
            'tanggalInput' => $tanggalInput,
            'jenis_kasir' => $jenis_kasir
        ]);
    }
    /**
     * Menampilkan Laporan Penerimaan Perkasir (Mockup Cetak)
     */
    public function showLaporanSesi($id)
    {
        $sesi = KasirSesi::findOrFail($id);

        $daftarTransaksi = KasirTagihanHead::select(
            'kasir_tagihan_head.simgos_norm as norm',
            'kasir_tagihan_head.nama_pasien as nama',
            'kasir_tagihan_head.simgos_tagihan_id as no_tagihan',
            'kasir_tagihan_head.total_bayar_pasien as tunai',
            'kasir_tagihan_head.total_bayar_asuransi as piutang',
            'kasir_pembayaran.created_at as waktu_bayar'
        )
        ->join('kasir_pembayaran', 'kasir_pembayaran.kasir_tagihan_head_id', '=', 'kasir_tagihan_head.id')
        ->where('kasir_pembayaran.kasir_sesi_id', $id)
        ->distinct()
        ->orderBy('kasir_pembayaran.created_at', 'asc')
        ->get();

        $totals = [
            'total_tunai' => $daftarTransaksi->sum('tunai'),
            'total_piutang' => $daftarTransaksi->sum('piutang'),
            'total_subsidi' => 0,
        ];

        // Nanti kita akan buat view cetak PDF untuk ini
        return view('laporan.laporan-sesi-detail', [ // <-- UBAH FOLDERNYA
            'sesi' => $sesi,
            'daftarTransaksi' => $daftarTransaksi,
            'totals' => $totals
        ]);
    }
    public function cetakLaporanSesi($id)
    {
        // 1. Logika pengambilan datanya SAMA PERSIS dengan showLaporanSesi
        $sesi = KasirSesi::findOrFail($id);

        $daftarTransaksi = KasirTagihanHead::select(
                'kasir_tagihan_head.simgos_norm as norm',
                'kasir_tagihan_head.nama_pasien as nama',
                'kasir_tagihan_head.simgos_tagihan_id as no_tagihan',
                'kasir_tagihan_head.total_bayar_pasien as tunai', 
                'kasir_tagihan_head.total_bayar_asuransi as piutang',
                'kasir_pembayaran.created_at as waktu_bayar'
            )
            ->join('kasir_pembayaran', 'kasir_pembayaran.kasir_tagihan_head_id', '=', 'kasir_tagihan_head.id')
            ->where('kasir_pembayaran.kasir_sesi_id', $id)
            ->distinct()
            ->orderBy('kasir_pembayaran.created_at', 'asc')
            ->get();

        $totals = [
            'total_tunai' => $daftarTransaksi->sum('tunai'),
            'total_piutang' => $daftarTransaksi->sum('piutang'),
            'total_subsidi' => 0,
        ];

        // 2. Siapkan data untuk view
        $dataUntukView = [
            'sesi' => $sesi,
            'daftarTransaksi' => $daftarTransaksi,
            'totals' => $totals
        ];

        // 3. Load View PDF (ini view baru!)
        //    (Sesuai idemu, kita gunakan folder 'reports' untuk cetakan)
        $pdf = PDF::loadView('reports.laporan-sesi-pdf', $dataUntukView);

        // 4. Set kertas ke A4 LANDSCAPE
        $pdf->setPaper('a4', 'landscape'); 

        // 5. Tampilkan PDF di browser
        return $pdf->stream('laporan-sesi-' . $sesi->id . '.pdf');
    }
}
