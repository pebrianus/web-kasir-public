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
    public function getAsuransiList()
    {
        $asuransiList = Referensi::select('ID', 'DESKRIPSI')
            ->where('JENIS', 10)
            ->where('STATUS', 1)
            ->orderBy('ID')
            ->get();

        return $asuransiList;
    }

    public function getPetugasList()
    {
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

        return $petugasList;
    }

    private function buildLaporanJasa(array $filter)
    {
        $tanggalDari = isset($filter['tanggal_dari']) ? $filter['tanggal_dari'] : null;
        $tanggalSampai = isset($filter['tanggal_sampai']) ? $filter['tanggal_sampai'] : null;
        $asuransi = isset($filter['asuransi']) ? $filter['asuransi'] : null;
        $petugasFilter = isset($filter['petugas']) ? $filter['petugas'] : null;
        $jenisPetugas = isset($filter['jenis_petugas']) ? $filter['jenis_petugas'] : null;

        /* =========================
         * 1. TAGIHAN KASIR (LUNAS)
         * ========================= */
        $queryTagihan = KasirTagihanHead::select(
            'simgos_tagihan_id',
            'simgos_norm',
            'nama_pasien',
            'nama_asuransi',
            'simgos_tanggal_tagihan'
        )->where('status_kasir', 'lunas');

        if ($asuransi && $asuransi !== 'Semua') {
            $queryTagihan->where('nama_asuransi', 'LIKE', '%' . $asuransi . '%');
        }

        if ($tanggalDari && $tanggalSampai) {
            $queryTagihan->whereBetween('simgos_tanggal_tagihan', array(
                Carbon::parse($tanggalDari)->startOfDay(),
                Carbon::parse($tanggalSampai)->endOfDay(),
            ));
        } else {
            $queryTagihan->whereDate('simgos_tanggal_tagihan', Carbon::today());
        }

        $tagihanHead = $queryTagihan->get();
        if ($tagihanHead->isEmpty()) {
            return collect();
        }

        /* =========================
         * 2. TAGIHAN RADIOLOGI
         * ========================= */
        $tagihanRadiologiIds = Tagihan::whereIn(
            'ID',
            $tagihanHead->pluck('simgos_tagihan_id')
        )->where('RADIOLOGI', '>', 0)->pluck('ID');

        if ($tagihanRadiologiIds->isEmpty()) {
            return collect();
        }

        $tagihanHeadRadiologi = $tagihanHead
            ->whereIn('simgos_tagihan_id', $tagihanRadiologiIds)
            ->values();

        /* =========================
         * 3. PENDAFTARAN → KUNJUNGAN
         * ========================= */
        $pendaftaran = TagihanPendaftaran::whereIn(
            'TAGIHAN',
            $tagihanRadiologiIds
        )->get(array('TAGIHAN', 'PENDAFTARAN'));

        $kunjungan = Kunjungan::whereIn(
            'NOPEN',
            $pendaftaran->pluck('PENDAFTARAN')
        )
            ->where('RUANGAN', '101030106')
            ->get(array('NOMOR', 'NOPEN'));

        /* =========================
         * 4. TARIF TERBARU
         * ========================= */
        $tarifTerbaru = DB::raw("
        (
            SELECT tt1.*
            FROM master.tarif_tindakan tt1
            WHERE tt1.STATUS = 1
              AND tt1.TANGGAL = (
                  SELECT MAX(tt2.TANGGAL)
                  FROM master.tarif_tindakan tt2
                  WHERE tt2.TINDAKAN = tt1.TINDAKAN
                    AND tt2.STATUS = 1
              )
        ) as tt
    ");

        /* =========================
         * 5. TINDAKAN
         * ========================= */
        $tindakan = TindakanMedis::join(
            DB::raw('master.tindakan as t'),
            't.ID',
            '=',
            'tindakan_medis.TINDAKAN'
        )
            ->leftJoin($tarifTerbaru, 'tt.TINDAKAN', '=', 'tindakan_medis.TINDAKAN')
            ->whereIn('tindakan_medis.KUNJUNGAN', $kunjungan->pluck('NOMOR'))
            ->select(array(
                'tindakan_medis.ID as TINDAKAN_MEDIS_ID',
                'tindakan_medis.KUNJUNGAN',
                't.NAMA as NAMA_TINDAKAN',
                'tindakan_medis.TANGGAL',
                'tt.DOKTER_OPERATOR',
                'tt.PARAMEDIS',
                'tt.TARIF',
            ))
            ->get();

        /* =========================
         * 6. PETUGAS
         * ========================= */
        $petugasTindakan = PetugasTindakanMedis::from('petugas_tindakan_medis as ptm')
            ->leftJoin('master.dokter as d', function ($j) {
                $j->on('d.ID', '=', 'ptm.MEDIS')
                    ->where('ptm.JENIS', 1);
            })
            ->leftJoin('master.perawat as pr', function ($j) {
                $j->on('pr.ID', '=', 'ptm.MEDIS')
                    ->where('ptm.JENIS', 3);
            })
            ->leftJoin(DB::raw('master.pegawai as p'), function ($j) {
                $j->on('p.NIP', '=', DB::raw("
                CASE
                    WHEN ptm.JENIS = 1 THEN d.NIP
                    WHEN ptm.JENIS = 3 THEN pr.NIP
                END
            "));
            })
            ->whereIn('ptm.TINDAKAN_MEDIS', $tindakan->pluck('TINDAKAN_MEDIS_ID'))
            ->where('ptm.STATUS', 1);

        if ($jenisPetugas) {
            $petugasTindakan->where('ptm.JENIS', $jenisPetugas);
        }

        if ($petugasFilter) {
            $petugasTindakan->where('ptm.MEDIS', $petugasFilter);
        }

        $petugasTindakan = $petugasTindakan
            ->select(array(
                'ptm.TINDAKAN_MEDIS',
                'ptm.JENIS',
                DB::raw("master.getNamaLengkapPegawai(p.NIP) as NAMA_PETUGAS"),
            ))
            ->get()
            ->groupBy('TINDAKAN_MEDIS');

        /* =========================
         * 7. RAKIT LAPORAN
         * ========================= */
        $laporan = $tagihanHeadRadiologi->map(function ($tagihan) use ($pendaftaran, $kunjungan, $tindakan, $petugasTindakan, $jenisPetugas) {

            $nopen = $pendaftaran
                ->where('TAGIHAN', $tagihan->simgos_tagihan_id)
                ->pluck('PENDAFTARAN');

            $kunjunganIds = $kunjungan
                ->whereIn('NOPEN', $nopen)
                ->pluck('NOMOR');

            $detailTindakan = $tindakan
                ->whereIn('KUNJUNGAN', $kunjunganIds)
                ->map(function ($tdk) use ($petugasTindakan, $jenisPetugas) {

                    if ($jenisPetugas == 1) {
                        $fee = (int) $tdk->DOKTER_OPERATOR;
                    } elseif ($jenisPetugas == 3) {
                        $fee = (int) $tdk->PARAMEDIS;
                    } else {
                        $fee = (int) $tdk->TARIF;
                    }

                    // ⛔ skip tindakan fee 0 saat filter petugas
                    if ($jenisPetugas && $fee <= 0) {
                        return null;
                    }

                    $petugas = isset($petugasTindakan[$tdk->TINDAKAN_MEDIS_ID])
                        ? $petugasTindakan[$tdk->TINDAKAN_MEDIS_ID]
                        : collect();

                    return array(
                        'nama_tindakan' => $tdk->NAMA_TINDAKAN,
                        'tanggal' => $tdk->TANGGAL,
                        'tarif' => (int) $tdk->TARIF,
                        'fee_petugas' => $fee,
                        'petugas' => $petugas->map(function ($p) {
                            return array(
                                'nama' => $p->NAMA_PETUGAS,
                                'jenis' => $p->JENIS,
                            );
                        })->values(),
                    );
                })
                ->filter()
                ->values();

            // ⛔ skip pasien jika tidak ada fee saat filter petugas
            if ($jenisPetugas && $detailTindakan->isEmpty()) {
                return null;
            }

            return array(
                'no_rm' => $tagihan->simgos_norm,
                'nama_pasien' => $tagihan->nama_pasien,
                'tanggal_tagihan' => $tagihan->simgos_tanggal_tagihan,
                'nama_asuransi' => $tagihan->nama_asuransi,
                'tindakan' => $detailTindakan,
                'total_tarif' => $detailTindakan->sum('tarif'),
                'total_fee' => $detailTindakan->sum('fee_petugas'),
            );
        });

        return $laporan->filter()->values();
    }


    public function indexJasa(Request $request)
    {
        if ($request->isMethod('post')) {

            session(array(
                'laporan_jasa_filter' => $request->only(array(
                    'tanggal_dari',
                    'tanggal_sampai',
                    'asuransi',
                    'petugas',
                    'jenis_petugas',
                ))
            ));

            return redirect()->route('laporan.jasa.index');
        }

        $filter = session('laporan_jasa_filter', array());

        $data = $this->buildLaporanJasa($filter);
        // dd($data->toArray());

        return view('laporan.index-jasa', array(
            'data' => $data,
            'asuransiList' => $this->getAsuransiList(),
            'petugasList' => $this->getPetugasList(),
        ));
    }

    public function cetakLaporanJasa()
    {
        $filter = session('laporan_jasa_filter', []);

        $tanggalDari = isset($filter['tanggal_dari'])
            ? $filter['tanggal_dari']
            : Carbon::today()->toDateString();

        $tanggalSampai = isset($filter['tanggal_sampai'])
            ? $filter['tanggal_sampai']
            : Carbon::today()->toDateString();

        $asuransi = isset($filter['asuransi'])
            ? $filter['asuransi']
            : 'Semua';

        $petugas = isset($filter['petugas'])
            ? $filter['petugas']
            : null;

        // ambil data laporan
        $data = $this->buildLaporanJasa($filter);

        // ===============================
// TESTING PDF: DUPLIKAT DATA 30x
// ===============================
        $testingMultiply = 30;

        $data = collect(range(1, $testingMultiply))
            ->flatMap(function () use ($data) {
                return $data;
            })
            ->values();


        $pdf = Pdf::loadView('reports.laporan-jasa', [
            'data' => $data,
            'tanggalDari' => $tanggalDari,
            'tanggalSampai' => $tanggalSampai,
            'asuransi' => $asuransi,
            'petugas' => $petugas,
        ])->setPaper('A4', 'portrait');

        $namaFile = 'Laporan-Jasa-Radiologi-' .
            Carbon::now()->format('YmdHis') . '.pdf';

        return $pdf->stream($namaFile);
    }
}
