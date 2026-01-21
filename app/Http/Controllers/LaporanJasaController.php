<?php

namespace App\Http\Controllers;

use App\Models\KasirSesi;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LaporanJasaController extends Controller
{
    //
    public function indexJasa(Request $request)
    {
        $jenis_kasir = $request->input('jenis');
        $tanggalInput = $request->input('tanggal');

        $query = KasirSesi::where('status', 'TUTUP')
            ->orderBy('waktu_tutup', 'desc');

        // Filter tanggal
        if ($tanggalInput) {
            $query->whereDate('waktu_buka', Carbon::parse($tanggalInput));
        } else {
            $query->whereDate('waktu_buka', Carbon::today());
            $tanggalInput = Carbon::today()->format('Y-m-d');
        }

        $daftarSesi = $query->paginate(20)->appends([
            'tanggal' => $tanggalInput,
            'jenis' => $jenis_kasir
        ]);

        $daftarSesi->getCollection()->transform(function ($sesi) {

            $totalPenerimaan = KasirPembayaran::where('kasir_sesi_id', $sesi->id)
                ->whereNull('deleted_at')
                ->sum('nominal_bayar');

            // inject property baru (tidak ke DB)
            $sesi->total_penerimaan_hitung = $totalPenerimaan;

            return $sesi;
        });

        return view('laporan.index-jasa', [
            'jenis_kasir' => $jenis_kasir,
            'tanggalInput' => $tanggalInput,
            'daftarSesi' => $daftarSesi

        ]);
    }
}
