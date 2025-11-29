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
    // public function indexPenerimaan(Request $request)
    // {
    //     $query = KasirSesi::where('status', 'TUTUP')
    //         ->orderBy('waktu_tutup', 'desc');

    //     $tanggalInput = $request->input('tanggal');
    //     $jenis_kasir = $request->input('jenis');

    //     if ($tanggalInput) {
    //         $tanggal = Carbon::parse($tanggalInput);
    //         $query->whereDate('waktu_buka', $tanggal);
    //     } else {
    //         $query->whereDate('waktu_buka', Carbon::today());
    //         $tanggalInput = Carbon::today()->format('Y-m-d');
    //     }

    //     $daftarSesi = $query->paginate(20)->appends([
    //         'tanggal' => $tanggalInput,
    //         'jenis' => $jenis_kasir
    //     ]);

    //     return view('laporan.index-penerimaan', [
    //         'daftarSesi' => $daftarSesi,
    //         'tanggalInput' => $tanggalInput,
    //         'jenis_kasir' => $jenis_kasir
    //     ]);
    // }
    public function indexPenerimaan(Request $request)
    {
        $tanggalInput = $request->input('tanggal');
        $jenis_kasir = $request->input('jenis'); // 1 / 2 / 3 (optional)

        $query = KasirSesi::where('status', 'TUTUP')
            ->orderBy('waktu_tutup', 'desc');

        // Filter tanggal
        if ($tanggalInput) {
            $tanggal = Carbon::parse($tanggalInput);
            $query->whereDate('waktu_buka', $tanggal);
        } else {
            $query->whereDate('waktu_buka', Carbon::today());
            $tanggalInput = Carbon::today()->format('Y-m-d');
        }

        // Filter jenis kasir jika dipilih
        if (!empty($jenis_kasir)) {
            $query->where('jenis_kasir', $jenis_kasir);
        }

        $daftarSesi = $query->paginate(20)->appends([
            'tanggal' => $tanggalInput,
            'jenis' => $jenis_kasir
        ]);

        return view('laporan.index-penerimaan', [
            'daftarSesi' => $daftarSesi,
            'tanggalInput' => $tanggalInput,
            'jenis_kasir' => $jenis_kasir
        ]);
    }

    /**
     * Menampilkan Laporan Penerimaan Perkasir (Mockup Cetak)
     */
    // public function showLaporanSesi(Request $request, $id)
    // {
    //     $jenis = $request->jenis; // 1 / 2 / 3

    //     $sesi = KasirSesi::where('id', $id)
    //         ->when($jenis, fn($q) => $q->where('jenis_kasir', $jenis))
    //         ->firstOrFail();

    //     $daftarTransaksi = KasirTagihanHead::select(
    //         'kasir_tagihan_head.simgos_norm as norm',
    //         'kasir_tagihan_head.nama_pasien as nama',
    //         'kasir_tagihan_head.simgos_tagihan_id as no_tagihan',
    //         'kasir_tagihan_head.total_bayar_pasien as tunai',
    //         'kasir_tagihan_head.total_bayar_asuransi as piutang',
    //         'kasir_pembayaran.created_at as waktu_bayar'
    //     )
    //         ->join('kasir_pembayaran', 'kasir_pembayaran.kasir_tagihan_head_id', '=', 'kasir_tagihan_head.id')
    //         ->where('kasir_pembayaran.kasir_sesi_id', $sesi->id)
    //         ->orderBy('kasir_pembayaran.created_at', 'asc')
    //         ->get();

    //     $totals = [
    //         'total_tunai' => $daftarTransaksi->sum('tunai'),
    //         'total_piutang' => $daftarTransaksi->sum('piutang'),
    //         'total_subsidi' => 0,
    //     ];

    //     return view('laporan.laporan-sesi-detail', [
    //         'sesi' => $sesi,
    //         'daftarTransaksi' => $daftarTransaksi,
    //         'totals' => $totals
    //     ]);
    // }
    public function showLaporanSesi(Request $request, $id)
    {
        $jenis = $request->jenis; // 1 / 2 / 3

        $sesi = KasirSesi::where('id', $id)
            ->when($jenis, fn($q) => $q->where('jenis_kasir', $jenis))
            ->firstOrFail();

        $daftarTransaksi = KasirTagihanHead::select(
            'kasir_tagihan_head.simgos_norm as norm',
            'kasir_tagihan_head.nama_pasien as nama',
            'kasir_tagihan_head.simgos_tagihan_id as no_tagihan',
            'kasir_tagihan_head.total_bayar_pasien as tunai',
            'kasir_tagihan_head.total_bayar_asuransi as piutang',
            'kasir_pembayaran.created_at as waktu_bayar'
        )
            ->join('kasir_pembayaran', 'kasir_pembayaran.kasir_tagihan_head_id', '=', 'kasir_tagihan_head.id')
            ->where('kasir_pembayaran.kasir_sesi_id', $sesi->id)
            ->orderBy('kasir_pembayaran.created_at', 'asc')
            ->get();

        $totals = [
            'total_tunai' => $daftarTransaksi->sum('tunai'),
            'total_piutang' => $daftarTransaksi->sum('piutang'),
            'total_subsidi' => 0,
        ];

        return view('laporan.laporan-sesi-detail', [
            'sesi' => $sesi,
            'daftarTransaksi' => $daftarTransaksi,
            'totals' => $totals
        ]);
    }


    public function cetakLaporanSesi(Request $request, $id)
    {
        // Ambil parameter ?jenis=1/2/3
        $jenis_kasir = $request->input('jenis');

        $jenisList = [
            1 => 'Rawat Jalan',
            2 => 'IGD',
            3 => 'Rawat Inap',
        ];

        $jenis_kasir_text = $jenisList[$jenis_kasir] ?? 'Tidak diketahui';

        // Data sesi
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

        // Kirim ke view PDF
        $dataUntukView = [
            'sesi' => $sesi,
            'daftarTransaksi' => $daftarTransaksi,
            'totals' => $totals,
            'jenis_kasir' => $jenis_kasir_text  // ⬅️ inilah yang dipakai di PDF
        ];

        $pdf = PDF::loadView('reports.laporan-sesi-pdf', $dataUntukView);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream('laporan-sesi-' . $sesi->id . '.pdf');
    }

}
