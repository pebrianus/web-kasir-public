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


    public function indexJasa(Request $request)
    {
        /* =========================
         * HANDLE FILTER (POST)
         * ========================= */
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
            'nama_asuransi'
        )
            ->where('status_kasir', 'lunas');

        if ($asuransi && $asuransi !== 'Semua') {
            $queryTagihan->where('nama_asuransi', 'LIKE', '%' . $asuransi . '%');
        }

        if ($tanggalDari && $tanggalSampai) {
            $queryTagihan->whereBetween('simgos_tanggal_tagihan', [
                Carbon::parse($tanggalDari)->startOfDay(),
                Carbon::parse($tanggalSampai)->endOfDay(),
            ]);
        } else {
            $queryTagihan->whereDate(
                'simgos_tanggal_tagihan',
                Carbon::today()
            );
        }

        $tagihanHead = $queryTagihan->get();

        if ($tagihanHead->isEmpty()) {
            return view('laporan.index-jasa', [
                'data' => [],
                'asuransiList' => $this->getAsuransiList(),
                'petugasList' => $this->getPetugasList(),
            ]);
        }

        // dd($tagihanHead->toArray());

        /* =========================
         * 2. TAGIHAN RADIOLOGI
         * ========================= */
        $tagihanRadiologiIds = Tagihan::whereIn(
            'ID',
            $tagihanHead->pluck('simgos_tagihan_id')
        )
            ->where('RADIOLOGI', '>', 0)
            ->pluck('ID');

        // dd($tagihanRadiologiIds);

        if ($tagihanRadiologiIds->isEmpty()) {
            return view('laporan.index-jasa', [
                'data' => [],
                'asuransiList' => $this->getAsuransiList(),
                'petugasList' => $this->getPetugasList(),
            ]);
        }

        /* =========================
         * 3. PENDAFTARAN → KUNJUNGAN RADIOLOGI
         * ========================= */
        $pendaftaranIds = TagihanPendaftaran::whereIn(
            'TAGIHAN',
            $tagihanRadiologiIds
        )
            ->pluck('PENDAFTARAN');

        $pendaftaran = TagihanPendaftaran::whereIn(
            'TAGIHAN',
            $tagihanRadiologiIds
        )
            ->get(['TAGIHAN', 'PENDAFTARAN']);


        $kunjungan = Kunjungan::whereIn(
            'NOPEN',
            $pendaftaran->pluck('PENDAFTARAN')
        )
            ->where('RUANGAN', '101030106')
            ->get(['NOMOR', 'NOPEN']);


        $kunjunganIds = $kunjungan->pluck('NOMOR');

        /* =========================
         * 4. SUBQUERY TARIF TERBARU
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
         * 5. TINDAKAN MEDIS + TARIF
         * ========================= */
        $tindakan = TindakanMedis::join(
            DB::raw('master.tindakan as t'),
            't.ID',
            '=',
            'tindakan_medis.TINDAKAN'
        )
            ->leftJoin($tarifTerbaru, 'tt.TINDAKAN', '=', 'tindakan_medis.TINDAKAN')
            ->whereIn('tindakan_medis.KUNJUNGAN', $kunjunganIds)
            ->select([
                'tindakan_medis.ID as TINDAKAN_MEDIS_ID',
                'tindakan_medis.KUNJUNGAN',
                'tindakan_medis.TINDAKAN',
                't.NAMA as NAMA_TINDAKAN',
                'tindakan_medis.TANGGAL',
                'tt.DOKTER_OPERATOR',
                'tt.PARAMEDIS',
                'tt.TARIF',
            ])
            ->get();

        // dd($tindakan->toArray());

        /* =========================
         * 6. PETUGAS PER TINDAKAN
         * ========================= */
        $petugasTindakan = PetugasTindakanMedis::join(
            DB::raw('master.pegawai as p'),
            'p.ID',
            '=',
            'petugas_tindakan_medis.MEDIS'
        )
            ->whereIn(
                'TINDAKAN_MEDIS',
                $tindakan->pluck('TINDAKAN_MEDIS_ID')
            )
            ->where('petugas_tindakan_medis.STATUS', 1);

        if ($jenisPetugas) {
            $petugasTindakan->where('petugas_tindakan_medis.JENIS', $jenisPetugas);
        }

        if ($petugasFilter) {
            $petugasTindakan->where('petugas_tindakan_medis.MEDIS', $petugasFilter);
        }

        $petugasTindakan = $petugasTindakan
            ->select([
                'petugas_tindakan_medis.TINDAKAN_MEDIS',
                'petugas_tindakan_medis.MEDIS',
                'petugas_tindakan_medis.JENIS',
                DB::raw("master.getNamaLengkapPegawai(p.NIP) as NAMA_PETUGAS"),
            ])
            ->get()
            ->groupBy('TINDAKAN_MEDIS');

        // dd($petugasTindakan->toArray());

        /* =========================
         * 7. RAKIT LAPORAN (FINAL)
         * ========================= */
        $laporan = $tagihanHead->map(function ($tagihan) use ($pendaftaran, $kunjungan, $tindakan, $petugasTindakan, $jenisPetugas) {

            // ambil NOPEN dari TAGIHAN
            $nopenPasien = $pendaftaran
                ->where('TAGIHAN', $tagihan->simgos_tagihan_id)
                ->pluck('PENDAFTARAN');

            // ambil NOMOR kunjungan
            $kunjunganPasien = $kunjungan
                ->whereIn('NOPEN', $nopenPasien)
                ->pluck('NOMOR');

            // ambil tindakan
            $tindakanPasien = $tindakan
                ->whereIn('KUNJUNGAN', $kunjunganPasien);

            $detailTindakan = $tindakanPasien->map(function ($tdk) use ($petugasTindakan, $jenisPetugas) {

                $petugas = isset($petugasTindakan[$tdk->TINDAKAN_MEDIS_ID])
                    ? $petugasTindakan[$tdk->TINDAKAN_MEDIS_ID]
                    : collect();

                $feePetugas = 0;

                // jika filter petugas dipilih
                if ($jenisPetugas == 1) {
                    $feePetugas = (int) $tdk->DOKTER_OPERATOR;
                } elseif ($jenisPetugas == 3) {
                    $feePetugas = (int) $tdk->PARAMEDIS;
                }
                // jika TIDAK filter petugas → pakai tarif
                else {
                    $feePetugas = (int) $tdk->TARIF;
                }


                return [
                    'nama_tindakan' => $tdk->NAMA_TINDAKAN,
                    'tanggal' => $tdk->TANGGAL,
                    'tarif' => (int) $tdk->TARIF,
                    'fee_petugas' => $feePetugas,
                    'petugas' => $petugas->map(function ($p) {
                        return [
                            'nama' => $p->NAMA_PETUGAS,
                            'jenis' => $p->JENIS,
                        ];
                    })->values(),
                ];
            });

            return [
                'no_rm' => $tagihan->simgos_norm,
                'nama_pasien' => $tagihan->nama_pasien,
                'tindakan' => $detailTindakan,
                'total_tarif' => $detailTindakan->sum('tarif'),
                'total_fee' => $detailTindakan->sum('fee_petugas'),
            ];
        });


        // dd($laporan->toArray());

        return view('laporan.index-jasa', [
            'data' => $laporan,
            'asuransiList' => $this->getAsuransiList(),
            'petugasList' => $this->getPetugasList(),
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
