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
        $tindakanFilter = isset($filter['tindakan']) ? $filter['tindakan'] : null;
        // dd($tindakan); // Debug untuk melihat nilai filter tindakan yang diterima

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
         * 4 & 5. TINDAKAN & TARIF HISTORIS (Saat Pasien Ditagih)
         * ========================= */

        $queryTindakan = TindakanMedis::join(DB::raw('master.tindakan as t'), 't.ID', '=', 'tindakan_medis.TINDAKAN')
            // 1. Join ke rincian_tagihan untuk mengunci nota transaksi
            ->leftJoin('pembayaran.rincian_tagihan as rt', function ($join) {
                $join->on('rt.REF_ID', '=', 'tindakan_medis.ID')
                    ->where('rt.JENIS', 3);
            })
            // 2. Join ke master tarif menggunakan TARIF_ID dari rincian_tagihan
            ->leftJoin('master.tarif_tindakan as tt', 'tt.ID', '=', 'rt.TARIF_ID')
            ->whereIn('tindakan_medis.KUNJUNGAN', $kunjungan->pluck('NOMOR'))
            ->where('tindakan_medis.STATUS', 1); // Filter tindakan aktif

        // Pengkondisian Filter Tindakan menggunakan LIKE pada tabel master.tindakan (alias t)
        if ($tindakanFilter && $tindakanFilter !== 'Semua') {
            $queryTindakan->where('t.NAMA', 'LIKE', '%' . $tindakanFilter . '%');
        }

        $tindakan = $queryTindakan->select(array(
            'tindakan_medis.ID as TINDAKAN_MEDIS_ID',
            'tindakan_medis.KUNJUNGAN',
            't.NAMA as NAMA_TINDAKAN',
            'tindakan_medis.TANGGAL',

            // Nilai ini sekarang adalah harga riwayat (historis)
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
        $laporan = $tagihanHeadRadiologi->map(function ($tagihan) use ($pendaftaran, $kunjungan, $tindakan, $petugasTindakan, $jenisPetugas, $petugasFilter) {

            $nopen = $pendaftaran
                ->where('TAGIHAN', $tagihan->simgos_tagihan_id)
                ->pluck('PENDAFTARAN');

            $kunjunganIds = $kunjungan
                ->whereIn('NOPEN', $nopen)
                ->pluck('NOMOR');

            $detailTindakan = $tindakan
                ->whereIn('KUNJUNGAN', $kunjunganIds)
                ->map(function ($tdk) use ($petugasTindakan, $jenisPetugas, $petugasFilter) {

                    // 1. Buat variabel khusus untuk mengecek apakah fee petugas spesifik tersebut 0
                    if ($jenisPetugas == 1) {
                        $feePengecekan = (int) $tdk->DOKTER_OPERATOR;
                    } elseif ($jenisPetugas == 3) {
                        $feePengecekan = (int) $tdk->PARAMEDIS;
                    } else {
                        $feePengecekan = (int) $tdk->TARIF;
                    }

                    $petugas = isset($petugasTindakan[$tdk->TINDAKAN_MEDIS_ID])
                        ? $petugasTindakan[$tdk->TINDAKAN_MEDIS_ID]
                        : collect();

                    if ($petugasFilter && $petugas->isEmpty()) {
                        return null;
                    }

                    // ⛔ skip tindakan fee 0 saat filter petugas
                    // Menggunakan $feePengecekan agar logika filter tidak bocor/rusak
                    if ($jenisPetugas && $feePengecekan <= 0) {
                        return null;
                    }

                    return array(
                        'nama_tindakan' => $tdk->NAMA_TINDAKAN,
                        'tanggal' => $tdk->TANGGAL,
                        'tarif' => (int) $tdk->TARIF,

                        // 👇 UBAH DI SINI: Tetapkan selalu mengambil TARIF agar konsisten (700.000)
                        'fee_petugas' => (int) $tdk->TARIF,

                        'petugas' => $petugas->map(function ($p) use ($tdk) {

                            $feeIndividu = 0;
                            if ($p->JENIS == 1) {
                                $feeIndividu = (int) $tdk->DOKTER_OPERATOR;
                            } elseif ($p->JENIS == 3) {
                                $feeIndividu = (int) $tdk->PARAMEDIS;
                            } else {
                                $feeIndividu = (int) $tdk->TARIF;
                            }

                            return array(
                                'nama' => $p->NAMA_PETUGAS,
                                'jenis' => $p->JENIS,
                                'fee' => $feeIndividu, // Fee individu dokter tetap 300.000 di dalam array ini
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

            // ==========================================
            // KODE TAMBAHAN UNTUK CEK TOTAL 0
            // ==========================================
            $totalTarif = $detailTindakan->sum('tarif');
            $totalFee = $detailTindakan->sum('fee_petugas');

            // ⛔ skip pasien jika total tarif dan fee sama-sama 0
            if ($totalTarif == 0 && $totalFee == 0) {
                return null;
            }
            // ==========================================

            return array(
                'no_rm' => $tagihan->simgos_norm,
                'nama_pasien' => $tagihan->nama_pasien,
                'tanggal_tagihan' => $tagihan->simgos_tanggal_tagihan,
                'nama_asuransi' => $tagihan->nama_asuransi,
                'tindakan' => $detailTindakan,
                'total_tarif' => $totalTarif,
                'total_fee' => $totalFee,
            );
        });

        // dd($laporan->toArray());

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
                    'tindakan',
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

    public function cetakLaporanJasa(Request $request)
    {
        $format = $request->query('format', 1);

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
        // $testingMultiply = 30;

        // $data = collect(range(1, $testingMultiply))
        //     ->flatMap(function () use ($data) {
        //         return $data;
        //     })
        //     ->values();


        $pdf = Pdf::loadView('reports.laporan-jasa', [
            'data' => $data,
            'tanggalDari' => $tanggalDari,
            'tanggalSampai' => $tanggalSampai,
            'asuransi' => $asuransi,
            'petugas' => $petugas,
            'format' => $format,
        ])->setPaper('A4', 'portrait');

        $namaFile = 'Laporan-Jasa-Radiologi-' .
            Carbon::now()->format('YmdHis') . '.pdf';

        return $pdf->stream($namaFile);
    }

    // Function jasa Lab

    public function getPetugasLabList()
    {
        $listSmfLab = [25, 28, 30];
        $idProfesiLab = 2;
        $smfString = implode(',', $listSmfLab);

        $petugasList = Pegawai::select(
            DB::raw("
                CASE
                    WHEN pegawai.PROFESI = $idProfesiLab THEN pegawai.ID
                    WHEN pegawai.SMF IN ($smfString) THEN dokter.ID
                    ELSE NULL
                END AS ID_PETUGAS
            "),
            Pegawai::selectNamaLengkap('nama_petugas'),
            DB::raw("
                CASE
                    WHEN pegawai.PROFESI = $idProfesiLab THEN 6
                    WHEN pegawai.SMF IN ($smfString) THEN 1
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
            ->where(function ($q) use ($listSmfLab, $idProfesiLab) {
                $q->whereIn('pegawai.SMF', $listSmfLab) // <--- PAKE WHERE IN
                    ->orWhere('pegawai.PROFESI', $idProfesiLab);
            })
            ->orderBy('nama_petugas')
            ->get();

        return $petugasList;
    }

    private function buildLaporanJasaLab(array $filter)
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
         * 2. TAGIHAN LABORATORIUM
         * ========================= */
        $tagihanLabIds = Tagihan::whereIn(
            'ID',
            $tagihanHead->pluck('simgos_tagihan_id')
        )->where('LABORATORIUM', '>', 0)->pluck('ID');

        if ($tagihanLabIds->isEmpty()) {
            return collect();
        }

        $tagihanHeadLab = $tagihanHead
            ->whereIn('simgos_tagihan_id', $tagihanLabIds)
            ->values();

        /* =========================
         * 3. PENDAFTARAN → KUNJUNGAN
         * ========================= */

        $idRuanganLab = [
            '101030507', // Laboratorium
            '101030508', // Laboratorium MCU
            '101030509', // Laboratorium Rujukan
            '101030510', // BANK DARAH (Opsional: Masukkan jika Bank Darah juga dihitung jasa Lab-nya)
        ];

        $pendaftaran = TagihanPendaftaran::whereIn(
            'TAGIHAN',
            $tagihanLabIds
        )->get(array('TAGIHAN', 'PENDAFTARAN'));

        $kunjungan = Kunjungan::whereIn(
            'NOPEN',
            $pendaftaran->pluck('PENDAFTARAN')
        )
            ->whereIn('RUANGAN', $idRuanganLab)
            ->get(array('NOMOR', 'NOPEN'));

        /* =========================
         * 4 & 5. TINDAKAN & TARIF HISTORIS (Saat Pasien Ditagih)
         * ========================= */

        // Block $tarifTerbaru sudah dihapus

        $tindakan = TindakanMedis::join(DB::raw('master.tindakan as t'), 't.ID', '=', 'tindakan_medis.TINDAKAN')
            // 1. Join ke rincian_tagihan untuk mengunci nota transaksi
            ->leftJoin('pembayaran.rincian_tagihan as rt', function ($join) {
                $join->on('rt.REF_ID', '=', 'tindakan_medis.ID')
                    ->where('rt.JENIS', 3);
                // Catatan: Pastikan JENIS = 3 di tabel referensi kalian memang merujuk ke Tindakan Medis/Lab.
            })
            // 2. Join ke master tarif menggunakan TARIF_ID dari rincian_tagihan
            ->leftJoin('master.tarif_tindakan as tt', 'tt.ID', '=', 'rt.TARIF_ID')
            ->whereIn('tindakan_medis.KUNJUNGAN', $kunjungan->pluck('NOMOR'))
            ->where('tindakan_medis.STATUS', 1) // Filter tindakan aktif
            ->select(array(
                'tindakan_medis.ID as TINDAKAN_MEDIS_ID',
                'tindakan_medis.KUNJUNGAN',
                't.NAMA as NAMA_TINDAKAN',
                'tindakan_medis.TANGGAL',

                // Nilai ini sekarang adalah harga riwayat (historis)
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
            ->leftJoin('master.pegawai as p_langsung', function ($j) {
                $j->on('p_langsung.ID', '=', 'ptm.MEDIS') // Langsung ke ID Pegawai
                    ->where('ptm.JENIS', 6);
            })
            ->leftJoin(DB::raw('master.pegawai as p'), function ($j) {
                $j->on('p.NIP', '=', DB::raw("
                CASE
                    WHEN ptm.JENIS = 1 THEN d.NIP
                    WHEN ptm.JENIS = 6 THEN p_langsung.NIP
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
        $laporan = $tagihanHeadLab->map(function ($tagihan) use ($pendaftaran, $kunjungan, $tindakan, $petugasTindakan, $jenisPetugas, $petugasFilter) {

            $nopen = $pendaftaran
                ->where('TAGIHAN', $tagihan->simgos_tagihan_id)
                ->pluck('PENDAFTARAN');

            $kunjunganIds = $kunjungan
                ->whereIn('NOPEN', $nopen)
                ->pluck('NOMOR');

            $detailTindakan = $tindakan
                ->whereIn('KUNJUNGAN', $kunjunganIds)
                ->map(function ($tdk) use ($petugasTindakan, $jenisPetugas, $petugasFilter) {

                    if ($jenisPetugas == 1) {
                        $fee = (int) $tdk->DOKTER_OPERATOR;
                    } elseif ($jenisPetugas == 6) {
                        $fee = (int) $tdk->PARAMEDIS;
                    } else {
                        $fee = (int) $tdk->TARIF;
                    }

                    $petugas = isset($petugasTindakan[$tdk->TINDAKAN_MEDIS_ID])
                        ? $petugasTindakan[$tdk->TINDAKAN_MEDIS_ID]
                        : collect();

                    if ($petugasFilter && $petugas->isEmpty()) {
                        return null;
                    }

                    // ⛔ skip tindakan fee 0 saat filter petugas
                    if ($jenisPetugas && $fee <= 0) {
                        return null;
                    }

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

    public function indexJasaLab(Request $request)
    {
        if ($request->isMethod('post')) {

            session(array(
                'laporan_jasa_lab_filter' => $request->only(array(
                    'tanggal_dari',
                    'tanggal_sampai',
                    'asuransi',
                    'petugas',
                    'jenis_petugas',
                ))
            ));

            return redirect()->route('laporan.jasa.lab.index');
        }

        $filter = session('laporan_jasa_lab_filter', array());

        $data = $this->buildLaporanJasaLab($filter);
        // dd($data->toArray());

        return view('laporan.index-jasa-lab', array(
            'data' => $data,
            'asuransiList' => $this->getAsuransiList(),
            'petugasList' => $this->getPetugasLabList(),
        ));
    }

    public function cetakLaporanJasaLab()
    {
        $filter = session('laporan_jasa_lab_filter', []);
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
        $data = $this->buildLaporanJasaLab($filter);

        // ===============================
        // TESTING PDF: DUPLIKAT DATA 30x
        // ===============================
        // $testingMultiply = 30;

        // $data = collect(range(1, $testingMultiply))
        //     ->flatMap(function () use ($data) {
        //         return $data;
        //     })
        //     ->values();


        $pdf = Pdf::loadView('reports.laporan-jasa-lab', [
            'data' => $data,
            'tanggalDari' => $tanggalDari,
            'tanggalSampai' => $tanggalSampai,
            'asuransi' => $asuransi,
            'petugas' => $petugas,
        ])->setPaper('A4', 'portrait');

        $namaFile = 'Laporan-Jasa-Lab-' .
            Carbon::now()->format('YmdHis') . '.pdf';

        return $pdf->stream($namaFile);
    }

    public function indexJasaDokter(Request $request)
    {
        if ($request->isMethod('post')) {

            session(array(
                'laporan_jasa_dokter_filter' => $request->only(array(
                    'tanggal_dari',
                    'tanggal_sampai',
                    'asuransi',
                    'petugas',
                    'dokter',
                    'jenis_petugas',
                ))
            ));

            return redirect()->route('laporan.jasa.dokter.index');
        }

        $filter = session('laporan_jasa_dokter_filter', array());

        $data = $this->buildLaporanJasaDokter($filter);
        // dd($data->toArray());


        return view('laporan.index-jasa-dokter', array(
            'data' => $data,
            'asuransiList' => $this->getAsuransiList(),
            'dokterList' => $this->getDokterList(),
        ));
    }

    public function getDokterList()
    {
        $dokterList = Pegawai::select(
            'dokter.ID as ID_DOKTER',
            Pegawai::selectNamaLengkap('nama_dokter'),
        )
            ->join('dokter', function ($join) {
                $join->on('dokter.NIP', '=', 'pegawai.NIP')
                    ->where('dokter.STATUS', 1);
            })
            ->where('pegawai.STATUS', 1)
            ->orderBy('nama_dokter')
            ->get();


        // dd($dokterList->toArray());
        return $dokterList;
    }

    private function buildLaporanJasaDokter(array $filter)
    {
        $tanggalDari = isset($filter['tanggal_dari']) ? $filter['tanggal_dari'] : null;
        $tanggalSampai = isset($filter['tanggal_sampai']) ? $filter['tanggal_sampai'] : null;
        $asuransi = isset($filter['asuransi']) ? $filter['asuransi'] : null;
        $petugasFilter = isset($filter['dokter']) ? $filter['dokter'] : null;
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
         * 2. TAGIHAN DOKTER
         * ========================= */
        $tagihanDokterIds = Tagihan::whereIn(
            'ID',
            $tagihanHead->pluck('simgos_tagihan_id')
        )->where(function ($q) {
            $q->where('PROSEDUR_NON_BEDAH', '>', 0)
                ->orWhere('PROSEDUR_BEDAH', '>', 0)
                ->orWhere('KONSULTASI', '>', 0)
                ->orWhere('TENAGA_AHLI', '>', 0)
                ->orWhere('KEPERAWATAN', '>', 0);
        })->pluck('ID');

        $tagihanHeadDokter = $tagihanHead
            ->whereIn('simgos_tagihan_id', $tagihanDokterIds)
            ->values();

        /* =========================
         * 3. PENDAFTARAN → KUNJUNGAN
         * ========================= */

        $pendaftaran = TagihanPendaftaran::whereIn(
            'TAGIHAN',
            $tagihanDokterIds
        )->get(array('TAGIHAN', 'PENDAFTARAN'));

        $kunjungan = Kunjungan::whereIn(
            'NOPEN',
            $pendaftaran->pluck('PENDAFTARAN')
        )
            // ->whereIn('RUANGAN', $idRuanganLab)
            ->get(array('NOMOR', 'NOPEN'));


        /* =========================
         * 4 & 5. TINDAKAN & TARIF HISTORIS (Saat Pasien Ditagih)
         * ========================= */

        $tindakan = TindakanMedis::join(DB::raw('master.tindakan as t'), 't.ID', '=', 'tindakan_medis.TINDAKAN')
            // 1. Sambungkan ke rincian_tagihan untuk melihat 'nota' spesifik tindakan ini
            // (Asumsi rt.JENIS = 3 merujuk ke tindakan medis, sesuai dengan Stored Procedure-mu)
            ->leftJoin('pembayaran.rincian_tagihan as rt', function ($join) {
                $join->on('rt.REF_ID', '=', 'tindakan_medis.ID')
                    ->where('rt.JENIS', 3);
            })
            // 2. Sambungkan ke tabel master tarif berbekal TARIF_ID dari rincian_tagihan
            // Ini otomatis menarik harga riwayat saat transaksi terjadi
            ->leftJoin('master.tarif_tindakan as tt', 'tt.ID', '=', 'rt.TARIF_ID')

            ->whereIn('tindakan_medis.KUNJUNGAN', $kunjungan->pluck('NOMOR'))
            ->whereIn('tindakan_medis.STATUS', [1]) // Filter tindakan aktif
            ->select(array(
                'tindakan_medis.ID as TINDAKAN_MEDIS_ID',
                'tindakan_medis.KUNJUNGAN',
                't.NAMA as NAMA_TINDAKAN',
                'tindakan_medis.TANGGAL',

                // Nilai di bawah ini sekarang dijamin adalah nilai historis (harga lama)
                'tt.DOKTER_OPERATOR',
                'tt.DOKTER_ANASTESI',
                'tt.PARAMEDIS',
                'tt.TARIF',

                // Boleh ditambahkan jika butuh ngecek:
                // 'rt.TARIF_ID',
            ))
            ->get();

        // dd($tindakan->toArray());

        /* =========================
         * 6. PETUGAS
         * ========================= */
        $petugasTindakan = PetugasTindakanMedis::from('petugas_tindakan_medis as ptm')
            ->leftJoin('master.dokter as d', function ($j) {
                $j->on('d.ID', '=', 'ptm.MEDIS')
                    ->whereIn('ptm.JENIS', [1, 2]);
            })
            ->leftJoin('master.perawat as pr', function ($j) {
                $j->on('pr.ID', '=', 'ptm.MEDIS')
                    ->where('ptm.JENIS', 3);
            })
            ->leftJoin('master.pegawai as p_langsung', function ($j) {
                $j->on('p_langsung.ID', '=', 'ptm.MEDIS') // Langsung ke ID Pegawai
                    ->where('ptm.JENIS', 6);
            })
            ->leftJoin(DB::raw('master.pegawai as p'), function ($j) {
                $j->on('p.NIP', '=', DB::raw("
                CASE
                    WHEN ptm.JENIS IN (1, 2) THEN d.NIP
                        WHEN ptm.JENIS = 3 THEN pr.NIP
                    WHEN ptm.JENIS = 6 THEN p_langsung.NIP
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


        // Kelompokkan pendaftaran berdasarkan TAGIHAN
        $pendaftaranGrouped = $pendaftaran->groupBy('TAGIHAN');

        // Kelompokkan kunjungan berdasarkan NOPEN
        $kunjunganGrouped = $kunjungan->groupBy('NOPEN');

        // Kelompokkan tindakan berdasarkan KUNJUNGAN
        $tindakanGrouped = $tindakan->groupBy('KUNJUNGAN');

        /* =========================
         * 7. RAKIT LAPORAN
         * ========================= */
        /* =========================
         * 7. RAKIT LAPORAN (OPTIMIZED)
         * ========================= */
        $laporan = $tagihanHeadDokter->map(function ($tagihan) use ($pendaftaranGrouped, $kunjunganGrouped, $tindakanGrouped, $petugasTindakan, $jenisPetugas, $petugasFilter) {

            // Ambil pendaftaran dari data yang sudah dikelompokkan (Sangat Cepat!)
            $nopen = $pendaftaranGrouped->get($tagihan->simgos_tagihan_id, collect())->pluck('PENDAFTARAN');

            // Ambil kunjungan yang sesuai
            $kunjunganIds = collect();
            foreach ($nopen as $np) {
                $kunjunganData = $kunjunganGrouped->get($np, collect());
                $kunjunganIds = $kunjunganIds->merge($kunjunganData->pluck('NOMOR'));
            }

            // Ambil detail tindakan berdasarkan kunjungan
            $detailTindakan = collect();
            foreach ($kunjunganIds as $kunjunganId) {
                $tindakanList = $tindakanGrouped->get($kunjunganId, collect());

                foreach ($tindakanList as $tdk) {
                    // Logika Fee
                    if ($jenisPetugas == 1) {
                        $fee = (int) $tdk->DOKTER_OPERATOR;
                    } elseif ($jenisPetugas == 6) {
                        $fee = (int) $tdk->PARAMEDIS;
                    } else {
                        $fee = (int) $tdk->TARIF;
                    }

                    // Langsung ambil petugas dari array/collection yang di-index ID Tindakan (karena sudah kamu groupBy di Langkah 6)
                    $petugas = isset($petugasTindakan[$tdk->TINDAKAN_MEDIS_ID])
                        ? collect($petugasTindakan[$tdk->TINDAKAN_MEDIS_ID])
                        : collect();

                    if ($petugasFilter && $petugas->isEmpty()) {
                        continue; // Skip
                    }

                    if ($jenisPetugas && $fee <= 0) {
                        continue; // Skip
                    }

                    $mappedPetugas = $petugas->map(function ($p) use ($tdk) {
                        $feeIndividu = 0;
                        if ($p->JENIS == 1) {
                            $feeIndividu = (int) $tdk->DOKTER_OPERATOR;
                        } elseif ($p->JENIS == 2) {
                            $feeIndividu = (int) $tdk->DOKTER_ANASTESI;
                        } elseif ($p->JENIS == 3) {
                            $feeIndividu = (int) $tdk->PARAMEDIS;
                        } else {
                            $feeIndividu = (int) $tdk->TARIF;
                        }

                        return [
                            'nama' => $p->NAMA_PETUGAS,
                            'jenis' => $p->JENIS,
                            'fee' => $feeIndividu,
                        ];
                    })->values();

                    $detailTindakan->push([
                        'nama_tindakan' => $tdk->NAMA_TINDAKAN,
                        'tanggal' => $tdk->TANGGAL,
                        'tarif' => (int) $tdk->TARIF,
                        'fee_petugas' => $fee,
                        'petugas' => $mappedPetugas,
                    ]);
                }
            }

            if ($jenisPetugas && $detailTindakan->isEmpty()) {
                return null;
            }

            $totalTarif = $detailTindakan->sum('tarif');
            $totalFee = $detailTindakan->sum('fee_petugas');

            if ($totalTarif == 0 && $totalFee == 0) {
                return null;
            }

            return [
                'no_rm' => $tagihan->simgos_norm,
                'nama_pasien' => $tagihan->nama_pasien,
                'tanggal_tagihan' => $tagihan->simgos_tanggal_tagihan,
                'nama_asuransi' => $tagihan->nama_asuransi,
                'tindakan' => $detailTindakan->values()->toArray(),
                'total_tarif' => $totalTarif,
                'total_fee' => $totalFee,
            ];
        })->filter()->values();

        return $laporan;
    }

    public function cetakLaporanJasaDokter(Request $request)
    {

        // Jika parameter format tidak ada di URL, otomatis set ke 1
        $format = $request->query('format', 1);

        $filter = session('laporan_jasa_dokter_filter', []);
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

        $data = $this->buildLaporanJasaDokter($filter);

        $pdf = Pdf::loadView('reports.laporan-jasa-dokter', [
            'data' => $data,
            'tanggalDari' => $tanggalDari,
            'tanggalSampai' => $tanggalSampai,
            'asuransi' => $asuransi,
            'petugas' => $petugas,
            'format' => $format,
        ])->setPaper('A4', 'portrait');

        $namaFile = 'Laporan-Jasa-Dokter-' .
            Carbon::now()->format('YmdHis') . '.pdf';

        return $pdf->stream($namaFile);
    }
}
