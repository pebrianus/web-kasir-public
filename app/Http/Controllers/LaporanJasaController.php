<?php

namespace App\Http\Controllers;

// use App\Models\KasirPembayaran;
use App\Models\KasirSesi;
use App\Models\KasirTagihanHead;
use App\Models\Kunjungan;
use App\Models\Pegawai;
use App\Models\PetugasTindakanMedis;
use App\Models\Referensi;
// use Barryvdh\DomPDF\PDF;
use App\Models\Tagihan;
use App\Models\TagihanPendaftaran;
use App\Models\TindakanMedis;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;


class LaporanJasaController extends Controller
{
    public function indexJasa(Request $request)
    {

        if ($request->isMethod('post')) {
            session([
                'laporan_jasa_filter' => $request->only([
                    'tanggal_dari',
                    'tanggal_sampai',
                    'asuransi',
                    'petugas',
                    'jenis_petugas',
                ])
            ]);

            return redirect()->route('laporan.jasa.index');
        }

        $filter = session('laporan_jasa_filter', []);

        $tanggalDari = $filter['tanggal_dari'] ?? null;
        $tanggalSampai = $filter['tanggal_sampai'] ?? null;
        $asuransi = $filter['asuransi'] ?? null;
        $petugas = $filter['petugas'] ?? null;
        $jenisPetugas = $filter['jenis_petugas'] ?? null;


        // dd($jenisPetugas);

        // dd($asuransi);

        // =========================
        // QUERY TAGIHAN LUNAS
        // =========================

        $queryTagihan = KasirTagihanHead::select(
            'simgos_tagihan_id',
            'simgos_norm',
            'nama_pasien',
            'nama_asuransi'
        )
            ->where('status_kasir', 'lunas')
            ->when($asuransi && $asuransi !== 'Semua', function ($q) use ($asuransi) {
                $q->where('nama_asuransi', 'LIKE', "%{$asuransi}%");
            });


        /* FILTER TANGGAL */
        if ($tanggalDari && $tanggalSampai) {
            $queryTagihan->whereBetween('simgos_tanggal_tagihan', [
                Carbon::parse($tanggalDari)->startOfDay(),
                Carbon::parse($tanggalSampai)->endOfDay(),
            ]);
        } else {
            $queryTagihan->whereDate('simgos_tanggal_tagihan', Carbon::today());
        }

        $tagihanHead = $queryTagihan->get();

        $data = $queryTagihan->pluck('simgos_tagihan_id');

        $tagihanRadiologi = Tagihan::whereIn('ID', $data)
            ->where('RADIOLOGI', '>', 0)
            ->pluck('ID');

        $pendaftaranIds = TagihanPendaftaran::whereIn('TAGIHAN', $tagihanRadiologi)
            ->pluck('PENDAFTARAN');

        $kunjunganIds = Kunjungan::whereIn('NOPEN', $pendaftaranIds)
            ->where('RUANGAN', '101030106')
            ->pluck('NOMOR');

        // dd($kunjunganIds->toArray());

        $tindakanIds = TindakanMedis::whereIn('KUNJUNGAN', $kunjunganIds)
            ->pluck('ID', 'TINDAKAN');

        // dd($tindakanIds->toArray());

        $tindakan = TindakanMedis::join(
            DB::raw('master.tindakan as t'),
            't.ID',
            '=',
            'tindakan_medis.TINDAKAN'
        )
            ->whereIn('tindakan_medis.KUNJUNGAN', $kunjunganIds)
            ->select([
                'tindakan_medis.TINDAKAN',
                't.NAMA as NAMA_TINDAKAN',
                'tindakan_medis.TANGGAL',
            ])
            ->get();

        // dd($tindakan->toArray());

        $petugas = $filter['petugas'] ?? null;

        $petugasTindakan = PetugasTindakanMedis::whereIn('TINDAKAN_MEDIS', $tindakanIds)
            ->where('STATUS', 1)

            // filter jenis petugas (kalau tidak null & bukan 0)
            ->when(!is_null($jenisPetugas) && $jenisPetugas != 0, function ($q) use ($jenisPetugas) {
                $q->where('JENIS', $jenisPetugas);
            })

            // filter petugas (kalau tidak null & bukan 0)
            ->when(!is_null($petugas) && $petugas != 0, function ($q) use ($petugas) {
                $q->where('MEDIS', $petugas);
            })

            ->pluck('MEDIS', 'TINDAKAN_MEDIS');


        // dd($petugasTindakan->toArray());

        // dd($tagihanRadiologi);

        // =========================
        // LIST FILTER (punyamu)
        // =========================
        $asuransiList = Referensi::select('ID', 'DESKRIPSI')
            ->where('JENIS', 10)
            ->where('STATUS', 1)
            ->orderBy('ID')
            ->get();


        $petugasList = Pegawai::select(
            DB::raw("
        CASE
            WHEN pegawai.PROFESI = 8 THEN perawat.ID
            WHEN pegawai.SMF = 27 THEN dokter.ID
            ELSE NULL
        END AS ID_PETUGAS
    "),
            Pegawai::selectNamaLengkap('nama_petugas'),
            DB::raw("
        CASE
            WHEN pegawai.PROFESI = 8 THEN 3
            WHEN pegawai.SMF = 27 THEN 1
            ELSE NULL
        END AS JENIS
    ")
        )
            ->leftJoin('perawat', function ($join) {
                $join->on('perawat.NIP', '=', 'pegawai.NIP')
                    ->where('perawat.STATUS', 1);
            })
            ->leftJoin('dokter', function ($join) {
                $join->on('dokter.NIP', '=', 'pegawai.NIP')
                    ->where('dokter.STATUS', 1);
            })
            ->where('pegawai.STATUS', 1)
            ->where(function ($q) {
                $q->where('pegawai.SMF', 27)
                    ->orWhere('pegawai.PROFESI', 8);
            })
            ->orderBy('nama_petugas')
            ->get();



        // dd($petugasList->toArray());

        return view('laporan.index-jasa', [

            // 'tanggalInput' => $tanggalInput,
            'asuransiList' => $asuransiList,
            'petugasList' => $petugasList,
            'data' => [],
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
