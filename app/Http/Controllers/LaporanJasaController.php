<?php

namespace App\Http\Controllers;

// use App\Models\KasirPembayaran;
use App\Models\KasirSesi;
use App\Models\Pegawai;
use App\Models\Referensi;
// use Barryvdh\DomPDF\PDF;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;


class LaporanJasaController extends Controller
{

public function indexJasa(Request $request)
{
    // =========================
    // PARAM UMUM (punyamu)
    // =========================
    $jenis_kasir  = $request->input('jenis');
    $tanggalInput = $request->input('tanggal');

    $query = KasirSesi::where('status', 'TUTUP')
        ->orderBy('waktu_tutup', 'desc');

    if ($tanggalInput) {
        $query->whereDate('waktu_buka', Carbon::parse($tanggalInput));
    } else {
        $query->whereDate('waktu_buka', Carbon::today());
        $tanggalInput = Carbon::today()->format('Y-m-d');
    }

    // =========================
    // LIST FILTER (punyamu)
    // =========================
    $asuransiList = Referensi::select('ID', 'DESKRIPSI')
        ->where('JENIS', 10)
        ->where('STATUS', 1)
        ->orderBy('DESKRIPSI')
        ->get();

    $petugasList = Pegawai::select(
        'NIP',
        Pegawai::selectNamaLengkap('nama_petugas')
    )
        ->where('STATUS', 1)
        ->where(function ($q) {
            $q->where('SMF', 27)
              ->orWhere('PROFESI', 8);
        })
        ->orderBy('nama_petugas')
        ->get();

    // =========================
    // DEFAULT VIEW DATA
    // =========================
    $data = [];

    // =========================
    // JIKA ADA REQUEST SEARCH
    // =========================
    if ($request->has('tanggal_dari')) {

        $tanggalDari   = $request->tanggal_dari;
        $tanggalSampai = $request->tanggal_sampai;

        $asuransi = $request->asuransi ?? 0;
        $petugas  = $request->petugas ?? 0;

        // sementara hardcode / nanti dari form
        $ruanganInput = $request->ruangan ?? null;

        // =========================
        // LOGIC RUANGAN
        // =========================
        $ruangan = 0;

        if (!empty($ruanganInput)) {
            if (is_numeric($ruanganInput)) {
                $ruangan = $ruanganInput;
            } else {
                $ruang = DB::table('master.ruangan')
                    ->where('DESKRIPSI', 'LIKE', '%' . $ruanganInput . '%')
                    ->first();

                $ruangan = $ruang ? $ruang->ID : 0;
            }
        }

        $tglAwal  = $tanggalDari . ' 00:00:00';
        $tglAkhir = $tanggalSampai . ' 23:59:59';

        // =========================
        // CALL STORED PROCEDURE
        // =========================
        $data = DB::select(
            'CALL laporan.LaporanJasaDokterPerPasien(?, ?, ?, ?, ?)',
            [$tglAwal, $tglAkhir, $ruangan, $asuransi, $petugas]
        );

        // DEBUG kalau perlu
        dd($data);
    }

    // =========================
    // RETURN VIEW
    // =========================
    return view('laporan.index-jasa', [
        'jenis_kasir'   => $jenis_kasir,
        'tanggalInput'  => $tanggalInput,
        'asuransiList'  => $asuransiList,
        'petugasList'   => $petugasList,
        'data'          => $data,
    ]);
}



    public function cariLaporanJasa(Request $request)
    {
        $tanggalDari = $request->tanggal_dari;
        $tanggalSampai = $request->tanggal_sampai;
        // Pake Placeholder dulu
        // $asuransi = $request->asuransi ?? 0;
        $asuransi = 0;
        // $petugas = $request->petugas ?? 0;
        $petugas = 313;
        // $ruanganInput = $request->ruangan ?? 0;
        $ruanganInput = "RADIOLOGI";

        /**
         * RULE RUANGAN:
         * - angka (101030106) → dipakai langsung
         * - teks ("RADIOLOGI") → cari ID ruangan
         * - kosong → 0 (semua)
         */
        $ruangan = 0;

        if (!empty($ruanganInput)) {
            if (is_numeric($ruanganInput)) {
                // contoh: 101030106
                $ruangan = $ruanganInput;
            } else {
                // contoh: "RADIOLOGI"
                $ruang = DB::table('master.ruangan')
                    ->where('DESKRIPSI', 'LIKE', '%' . $ruanganInput . '%')
                    ->first();

                $ruangan = $ruang ? $ruang->ID : 0;
            }
        }

        // Pastikan format datetime
        $tglAwal = $tanggalDari . ' 00:00:00';
        $tglAkhir = $tanggalSampai . ' 23:59:59';

        // Panggil Stored Procedure
        $data = DB::select(
            'CALL laporan.LaporanJasaDokterPerPasien(?, ?, ?, ?, ?)',
            [
                $tglAwal,
                $tglAkhir,
                $ruangan,
                $asuransi,
                $petugas
            ]
        );
        dd($data);
        return view('laporan.index-jasa', [
            'data' => $data,
            'tanggalDari' => $tanggalDari,
            'tanggalSampai' => $tanggalSampai,
            'ruangan' => $ruanganInput,
            'asuransi' => $asuransi,
            'petugas' => $petugas,
        ]);
    }

    public function cetakLaporanJasa(Request $request)
    {
        $tanggalDari = $request->tanggal_dari;
        $tanggalSampai = $request->tanggal_sampai;
        $asuransi = $request->asuransi;
        $petugas = $request->petugas;

        // Ambil nama petugas
        $namaPetugas = 'Semua-Petugas';
        if ($petugas) {
            $pegawai = Pegawai::where('NIP', $petugas)->first();
            if ($pegawai) {
                $namaPetugas = str_replace(' ', '-', $pegawai->nama_petugas);
            }
        }

        // Format tanggal untuk nama file
        $td = Carbon::parse($tanggalDari)->format('Ymd');
        $ts = Carbon::parse($tanggalSampai)->format('Ymd');

        $dataUntukView = compact(
            'tanggalDari',
            'tanggalSampai',
            'asuransi',
            'petugas'
        );

        $pdf = PDF::loadView('reports.laporan-jasa', $dataUntukView)
            ->setPaper('A4', 'portrait');

        $namaFile = "Laporan-Jasa-{$td}-{$ts}-{$namaPetugas}.pdf";

        return $pdf->stream($namaFile);
    }



}
