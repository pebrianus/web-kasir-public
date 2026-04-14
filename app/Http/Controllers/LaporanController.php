<?php

namespace App\Http\Controllers;

use App\Models\KasirPembayaran;
use App\Models\KasirPenjualanHead;
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
        $tanggalInput = $request->input('tanggal');
        $jenis_kasir = $request->input('jenis'); // 1 / 2 / 3

        $query = KasirSesi::where('status', 'TUTUP')
            ->orderBy('waktu_tutup', 'desc');

        // Filter tanggal
        if ($tanggalInput) {
            $query->whereDate('waktu_buka', Carbon::parse($tanggalInput));
        } else {
            $query->whereDate('waktu_buka', Carbon::today());
            $tanggalInput = Carbon::today()->format('Y-m-d');
        }

        // Filter jenis kasir
        if (!empty($jenis_kasir)) {
            $query->where('jenis_kasir', $jenis_kasir);
        }

        $daftarSesi = $query->paginate(20)->appends([
            'tanggal' => $tanggalInput,
            'jenis' => $jenis_kasir
        ]);

        // 🔥 HITUNG MANUAL TOTAL PENERIMAAN PER SESI
        $daftarSesi->getCollection()->transform(function ($sesi) {

            $totalPenerimaan = KasirPembayaran::where('kasir_sesi_id', $sesi->id)
                ->whereNull('deleted_at')
                ->sum('nominal_bayar');

            // inject property baru (tidak ke DB)
            $sesi->total_penerimaan_hitung = $totalPenerimaan;

            return $sesi;
        });

        return view('laporan.index-penerimaan', [
            'daftarSesi' => $daftarSesi,
            'tanggalInput' => $tanggalInput,
            'jenis_kasir' => $jenis_kasir
        ]);
    }

    /**
     * Menampilkan Laporan Penerimaan Perkasir (Mockup Cetak)
     */

    public function showLaporanSesi(Request $request, $id)
    {
        $jenis = $request->jenis; // 1 / 2 / 3

        $sesi = KasirSesi::where('id', $id)
            ->when($jenis, fn($q) => $q->where('jenis_kasir', $jenis))
            ->firstOrFail();

        if ($jenis == 6) {

            $daftarTransaksi = KasirPenjualanHead::select(
                DB::raw("'-' as norm"), // Tidak ada No RM
                'kasir_penjualan_heads.nama_pengunjung as nama',
                'kasir_penjualan_heads.simgos_penjualan_id as no_tagihan',
                'kasir_penjualan_heads.total_tagihan as tunai', // Anggap tunai
                DB::raw("0 as piutang"), // Tidak ada asuransi
                'kasir_pembayaran.keterangan as keterangan',
                'kasir_pembayaran.created_at as waktu_bayar'
            )
                ->join('kasir_pembayaran', function ($join) {
                    $join->on('kasir_pembayaran.kasir_penjualan_head_id', '=', 'kasir_penjualan_heads.id')
                        ->whereNull('kasir_pembayaran.deleted_at');
                })
                ->where('kasir_pembayaran.kasir_sesi_id', $sesi->id)
                ->orderBy('kasir_pembayaran.created_at', 'asc')
                ->get();

        } else {

            $daftarTransaksi = KasirTagihanHead::select(
                'kasir_tagihan_head.simgos_norm as norm',
                'kasir_tagihan_head.nama_pasien as nama',
                'kasir_tagihan_head.simgos_tagihan_id as no_tagihan',
                'kasir_tagihan_head.total_bayar_pasien as tunai',
                'kasir_tagihan_head.total_bayar_asuransi as piutang',
                'kasir_pembayaran.keterangan as keterangan',
                'kasir_pembayaran.created_at as waktu_bayar'
            )
                ->join('kasir_pembayaran', function ($join) {
                    $join->on('kasir_pembayaran.kasir_tagihan_head_id', '=', 'kasir_tagihan_head.id')
                        ->whereNull('kasir_pembayaran.deleted_at');
                })
                ->where('kasir_pembayaran.kasir_sesi_id', $sesi->id)
                ->orderBy('kasir_pembayaran.created_at', 'asc')
                ->get();
        }

        $totals = [
            'total_tunai' => $daftarTransaksi->sum('tunai'),
            'total_piutang' => $daftarTransaksi->sum('piutang'),
            'total_subsidi' => 0,
        ];

        // dd($daftarTransaksi->first());

        return view('laporan.laporan-sesi-detail', [
            'sesi' => $sesi,
            'daftarTransaksi' => $daftarTransaksi,
            'totals' => $totals
        ]);
    }


    public function cetakLaporanSesi(Request $request, $id)
    {
        $jenis_kasir = $request->input('jenis');

        $jenisList = [
            1 => 'Rawat Jalan',
            2 => 'IGD',
            3 => 'Rawat Inap',
            4 => 'Laboratorium',
            5 => 'Radiologi',
            6 => 'Farmasi',
        ];

        $jenis_kasir_text = $jenisList[$jenis_kasir] ?? 'Tidak diketahui';

        $sesi = KasirSesi::findOrFail($id);

        if ($jenis_kasir == 6) {

            // ✅ Farmasi → pakai kasir_penjualan_heads
            $daftarTransaksi = KasirPenjualanHead::select(
                DB::raw("'-' as norm"), // Tidak ada No RM
                'kasir_penjualan_heads.nama_pengunjung as nama',
                'kasir_penjualan_heads.simgos_penjualan_id as no_tagihan',
                'kasir_penjualan_heads.total_tagihan as tunai',
                DB::raw("0 as piutang"), // Tidak ada asuransi
                'kasir_pembayaran.keterangan as keterangan',
                'kasir_pembayaran.created_at as waktu_bayar'
            )
                ->join('kasir_pembayaran', function ($join) {
                    $join->on('kasir_pembayaran.kasir_penjualan_head_id', '=', 'kasir_penjualan_heads.id')
                        ->whereNull('kasir_pembayaran.deleted_at');
                })
                ->where('kasir_pembayaran.kasir_sesi_id', $id)
                ->orderBy('kasir_pembayaran.created_at', 'asc')
                ->get();

        } else {

            // ✅ Selain Farmasi → pakai kasir_tagihan_head
            $daftarTransaksi = KasirTagihanHead::select(
                'kasir_tagihan_head.simgos_norm as norm',
                'kasir_tagihan_head.nama_pasien as nama',
                'kasir_tagihan_head.simgos_tagihan_id as no_tagihan',
                'kasir_tagihan_head.total_bayar_pasien as tunai',
                'kasir_tagihan_head.total_bayar_asuransi as piutang',
                'kasir_pembayaran.keterangan as keterangan',
                'kasir_pembayaran.created_at as waktu_bayar'
            )
                ->join('kasir_pembayaran', function ($join) {
                    $join->on('kasir_pembayaran.kasir_tagihan_head_id', '=', 'kasir_tagihan_head.id')
                        ->whereNull('kasir_pembayaran.deleted_at');
                })
                ->where('kasir_pembayaran.kasir_sesi_id', $id)
                ->orderBy('kasir_pembayaran.created_at', 'asc')
                ->get();
        }

        $totals = [
            'total_tunai' => $daftarTransaksi->sum('tunai'),
            'total_piutang' => $daftarTransaksi->sum('piutang'),
            'total_subsidi' => 0,
        ];

        $dataUntukView = [
            'sesi' => $sesi,
            'daftarTransaksi' => $daftarTransaksi,
            'totals' => $totals,
            'jenis_kasir' => $jenis_kasir_text
        ];

        $pdf = PDF::loadView('reports.laporan-sesi-pdf', $dataUntukView);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream('laporan-sesi-' . $sesi->id . '.pdf');
    }

}
