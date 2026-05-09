<?php

namespace App\Http\Controllers;

use App\Models\KasirPiutangPembayaran;
use App\Models\KasirTagihanPiutang;
use Illuminate\Http\Request;
use App\Models\Pasien;
use App\Models\KasirTagihanHead;
use App\Models\KasirTagihanDetail;
use App\Models\AppReferensi;
use App\Models\KasirPembayaran;
use App\Models\KasirSesi;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PDF;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Carbon;

class KasirController extends Controller
{
    /**
     * Ambil nama asuransi dari SIMGOS berdasarkan ID tagihan.
     */
    private function ambilNamaAsuransiSimgos($simgosTagihanID)
    {
        return DB::connection('simgos_pembayaran')
            ->table('tagihan as t')
            // Perubahan di prod
            ->join('tagihan_pendaftaran as tp', 'tp.TAGIHAN', '=', 't.ID')
            ->join('pendaftaran.penjamin as pj', 'tp.PENDAFTARAN', '=', 'pj.NOPEN')
            ->join('master.referensi as ref_asuransi', function ($join) {
                $join->on('pj.JENIS', '=', 'ref_asuransi.ID')->where('ref_asuransi.JENIS', 10); // Jenis Asuransi
            })
            ->where('t.ID', $simgosTagihanID)
            ->value('ref_asuransi.DESKRIPSI'); // langsung ambil string
    }

    /**
     * Kembali ke fungsi semula:
     * Mencari data DAN menampilkan view
     */
    public function rawatJalan(Request $request)
    {
        // 1. Ambil kata kunci pencarian
        $searchQuery = $request->input('search');

        // Terima token jenis kasir (1=RJ, 2=IGD, 3=RI)
        $jenis_kasir = $request->input('jenis');

        // 2. Buat query dasar
        $pasienQuery = Pasien::whereIn('STATUS', [1, 2]);

        // 3. Jika ada pencarian, tambahkan logika 'where'
        if ($searchQuery) {
            $pasienQuery->where(function ($query) use ($searchQuery) {
                $query->where('NAMA', 'like', '%' . $searchQuery . '%')->orWhere('NORM', 'like', '%' . $searchQuery . '%');
            });
        }

        // 4. Ganti ->get() menjadi ->paginate()
        //    Kita ambil 10 data per halaman
        //    Urutkan berdasarkan NORM descending
        $pasienList = $pasienQuery->orderBy('NORM', 'desc')->paginate(10);

        // 5. Kirim data ke view
        return view('pencarian.rawat-jalan', [
            'pasienList' => $pasienList,
            'jenis_kasir' => $jenis_kasir, // Kirim juga jenis kasir ke view
        ]);
    }

    /**
     * Menampilkan halaman detail tagihan untuk satu pasien.
     */
    public function showTagihanPasien(Request $request, $norm, $jenis_kasir)
    {
        // Mengambil data pasien
        $pasien = Pasien::findOrFail($norm);
        // Ambil filter show tagihan, default 'proses'
        $statusFilter = $request->input('status', 'proses');

        // dd($statusFilter);

        $processedTags = KasirTagihanHead::where('simgos_norm', $norm)->select('id', 'simgos_tagihan_id', 'status_kasir')->get()->keyBy('simgos_tagihan_id');

        // 2. Ambil daftar tagihan (masih harga brutto)
        $daftarTagihanQuery = DB::connection('simgos_pembayaran')
            ->table('tagihan as t')
            ->join('tagihan_pendaftaran as tp', 'tp.TAGIHAN', '=', 't.ID')
            ->join('pendaftaran.kunjungan as k', 'tp.PENDAFTARAN', '=', 'k.NOPEN')
            ->join('pendaftaran.penjamin as pj', 'k.NOPEN', '=', 'pj.NOPEN')
            ->join('master.referensi as ref_asuransi', function ($join) {
                $join->on('pj.JENIS', '=', 'ref_asuransi.ID')
                    ->where('ref_asuransi.JENIS', 10);
            })
            ->join('master.ruangan as r', 'k.RUANGAN', '=', 'r.ID')
            ->join('master.dokter as d', 'k.DPJP', '=', 'd.ID')
            ->join('master.pegawai as p', 'd.NIP', '=', 'p.NIP')
            ->where('r.JENIS_KUNJUNGAN', $jenis_kasir)
            ->where('t.REF', $norm)
            ->where('t.STATUS', 2)
            ->where('tp.STATUS', 1)
            ->where('tp.UTAMA', 1)
            ->select(
                't.ID as no_tagihan',
                't.TOTAL as total_tagihan_kotor',
                't.TANGGAL as tgl_tagihan',
                'r.DESKRIPSI as nama_ruangan',
                'ref_asuransi.DESKRIPSI as nama_asuransi',
                DB::raw("CONCAT_WS(' ', p.GELAR_DEPAN, p.NAMA, p.GELAR_BELAKANG) as nama_dokter")
            )
            ->orderBy('t.TANGGAL', 'desc');


        $lunasIds = $processedTags->where('status_kasir', 'lunas')->pluck('simgos_tagihan_id');
        $piutangIds = $processedTags
            ->whereIn('status_kasir', ['piutang', 'outstanding'])
            ->pluck('simgos_tagihan_id');

        // Gabungkan kedua ID yang ingin dikecualikan
        $excludedIds = $lunasIds->merge($piutangIds);

        if ($statusFilter == 'proses') {
            // Tampilkan tagihan yang BELUM lunas DAN BUKAN piutang
            $daftarTagihanQuery->whereNotIn('t.ID', $excludedIds);
        } elseif ($statusFilter == 'piutang') {
            $daftarTagihanQuery->whereIn('t.ID', $piutangIds);
        } else {
            $daftarTagihanQuery->whereIn('t.ID', $lunasIds);
        }

        // $daftarTagihan = $daftarTagihanQuery->get();
        $daftarTagihan = $daftarTagihanQuery->get()->unique('no_tagihan')->values();

        // dd($daftarTagihan);

        // --- LOGIKA HITUNG DISKON & TOTAL BERSIH ---
        // Kita loop setiap tagihan untuk mengecek apakah ada diskon di SIMGOS
        foreach ($daftarTagihan as $tagihan) {
            // A. Ambil Diskon RS
            $diskonRSData = DB::connection('simgos_pembayaran')->table('diskon')->where('TAGIHAN', $tagihan->no_tagihan)->first();

            $totalDiskonRS = 0;
            if ($diskonRSData) {
                $totalDiskonRS = ($diskonRSData->ADMINISTRASI ?? 0) + ($diskonRSData->AKOMODASI ?? 0) + ($diskonRSData->SARANA_NON_AKOMODASI ?? 0) + ($diskonRSData->PARAMEDIS ?? 0);
            }

            // B. Ambil Diskon Dokter
            $totalDiskonDokter = DB::connection('simgos_pembayaran')->table('diskon_dokter')->where('TAGIHAN', $tagihan->no_tagihan)->sum('TOTAL');

            // C. Hitung Netto
            $totalDiskon = $totalDiskonRS + $totalDiskonDokter;
            $totalBersih = max(0, $tagihan->total_tagihan_kotor - $totalDiskon);

            // D. Masukkan data tambahan ke object tagihan agar bisa dibaca di View
            $tagihan->total_diskon = $totalDiskon;
            $tagihan->total_tagihan_bersih = $totalBersih; // <-- Ini yang akan ditampilkan
        }
        // --- [AKHIR LOGIKA BARU] ---

        return view('kasir.show-tagihan', [
            'pasien' => $pasien,
            'daftarTagihan' => $daftarTagihan,
            'processedTags' => $processedTags,
            'statusFilter' => $statusFilter,
            'jenis_kasir' => $jenis_kasir,
        ]);
    }

    public function prosesDanBukaTagihan(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'simgos_tagihan_id' => 'required',
        ]);

        $simgosTagihanID = $request->simgos_tagihan_id;

        // 2. CEK STATUS LOKAL SEBELUMNYA
        // Coba cari tagihan head lokal berdasarkan ID SIMGOS
        $existingTagihan = KasirTagihanHead::where('simgos_tagihan_id', $simgosTagihanID)->first();

        // JIKA SUDAH ADA DAN STATUSNYA 'lunas'
        if ($existingTagihan && $existingTagihan->status_kasir == 'lunas') {
            // Langsung arahkan ke halaman rincian lokal yang sudah ada
            // Jangan lakukan snapshot ulang!
            return redirect()
                ->route('kasir.tagihan.lokal', ['id' => $existingTagihan->id, 'jenis_kasir' => $request->jenis_kasir])
                ->with('info', 'Tagihan ini sudah lunas.');
            // Beri pesan 'info' (opsional)
        }

        // --- JIKA TIDAK ADA ATAU MASIH DRAFT: Lanjutkan proses snapshot ---

        // A. Ambil Diskon RS (Jumlahkan kolom-kolomnya)
        $diskonRSData = DB::connection('simgos_pembayaran')->table('diskon')->where('TAGIHAN', $simgosTagihanID)->first();

        $totalDiskonRS = 0;
        if ($diskonRSData) {
            // Jumlahkan komponen diskon (sesuaikan jika ada kolom lain di SIMGOS)
            $totalDiskonRS = ($diskonRSData->ADMINISTRASI ?? 0) + ($diskonRSData->AKOMODASI ?? 0) + ($diskonRSData->SARANA_NON_AKOMODASI ?? 0) + ($diskonRSData->PARAMEDIS ?? 0);
        }

        // B. Ambil Diskon Dokter
        $totalDiskonDokter = DB::connection('simgos_pembayaran')->table('diskon_dokter')->where('TAGIHAN', $simgosTagihanID)->sum('TOTAL');
        $totalDiskonRS = ($diskonRSData->ADMINISTRASI ?? 0) + ($diskonRSData->AKOMODASI ?? 0) + ($diskonRSData->SARANA_NON_AKOMODASI ?? 0) + ($diskonRSData->PARAMEDIS ?? 0);

        // B. Ambil Diskon Dokter
        $totalDiskonDokter = DB::connection('simgos_pembayaran')->table('diskon_dokter')->where('TAGIHAN', $simgosTagihanID)->sum('TOTAL');

        // C. Total Gabungan Diskon
        $totalDiskonSimgos = $totalDiskonRS + $totalDiskonDokter;

        // 3. Gunakan updateOrCreate seperti sebelumnya (aman karena sudah dicek)
        $tagihanHead = KasirTagihanHead::updateOrCreate(
            ['simgos_tagihan_id' => $simgosTagihanID], // Kunci pencarian
            // Data untuk di-update atau di-create
            [
                // Data untuk di-update atau di-create
                'simgos_norm' => $request->simgos_norm,
                'nama_pasien' => $request->nama_pasien,
                'nama_ruangan' => $request->nama_ruangan,
                'nama_dokter' => $request->nama_dokter,
                'nama_asuransi' => $request->nama_asuransi,
                'simgos_tanggal_tagihan' => $request->simgos_tanggal_tagihan,
                'total_asli_simgos' => $request->total_asli_simgos,
                'status_kasir' => 'draft', // Selalu set ke 'draft' saat snapshot
                'diskon_simgos' => $totalDiskonSimgos,
                'total_bayar_pasien' => max(0, $request->total_asli_simgos - $totalDiskonSimgos),
                'total_bayar_asuransi' => 0,
            ],
        );

        // 4. HAPUS detail lama (Wipe)
        KasirTagihanDetail::where('kasir_tagihan_head_id', $tagihanHead->id)->delete();

        // 5. Bangun "Resep Snapshot" (Replace)
        // --- Query UNION Anda yang sudah benar ada di sini ---
        $queryAdmin = DB::connection('simgos_pembayaran')
            ->table('rincian_tagihan as rt')
            ->join('master.referensi as ref', function ($join) {
                $join
                    ->on('ref.ID', '=', 'rt.JENIS')
                    ->where('ref.JENIS', 30);
            })
            ->where('rt.TAGIHAN', $simgosTagihanID)
            ->where('rt.JENIS', 1)
            ->where('rt.TARIF', '>', 0)
            ->select(
                'rt.REF_ID as simgos_ref_id',
                'rt.JENIS as simgos_jenis_tarif',
                DB::raw("'Administrasi' as deskripsi_item"),
                'rt.JUMLAH as qty',
                'rt.TARIF as harga_satuan',
            )
            ->union(
                // Tindakan yang JENIS = 16 (Administrasi) digabung ke sini
                DB::connection('simgos_pembayaran')
                    ->table('rincian_tagihan as rt')
                    ->join('layanan.tindakan_medis as tm', 'tm.ID', '=', 'rt.REF_ID')
                    ->join('master.tindakan as t', 't.ID', '=', 'tm.TINDAKAN')
                    ->where('rt.TAGIHAN', $simgosTagihanID)
                    ->where('rt.JENIS', 3)       // 3 = Tindakan
                    ->where('tm.STATUS', 1)      // Tindakan Medis Aktif
                    ->where('t.JENIS', 16)    // JENIS Administrasi
                    ->where('rt.TARIF', '>', 0)
                    ->select(
                        'rt.REF_ID as simgos_ref_id',
                        DB::raw("1 as simgos_jenis_tarif"), // paksa jadi 1 biar sama dengan administrasi
                        't.NAMA as deskripsi_item',
                        'rt.JUMLAH as qty',
                        'rt.TARIF as harga_satuan',
                    )
            );

        $queryTindakan = DB::connection('simgos_pembayaran')
            ->table('rincian_tagihan as rt')
            ->join('layanan.tindakan_medis as tm', 'tm.ID', '=', 'rt.REF_ID')
            ->join('master.tindakan as t', 't.ID', '=', 'tm.TINDAKAN')
            ->where('rt.TAGIHAN', '=', $simgosTagihanID)
            ->where('rt.JENIS', 3)
            ->where('tm.STATUS', 1)
            ->whereNotIn('t.JENIS', [16, 18]) // exclude Administrasi (16) dan Farmasi (18)
            ->select(
                'rt.REF_ID as simgos_ref_id',
                'rt.JENIS as simgos_jenis_tarif',
                't.NAMA as deskripsi_item',
                'rt.JUMLAH as qty',
                'rt.TARIF as harga_satuan',
            );

        $queryFarmasi = DB::connection('simgos_pembayaran')
            ->table('rincian_tagihan as rt')
            ->join('layanan.farmasi as f', 'f.ID', '=', 'rt.REF_ID')
            ->join('inventory.barang as b', 'b.ID', '=', 'f.FARMASI')
            ->where('rt.TAGIHAN', $simgosTagihanID)
            ->where('rt.JENIS', 4)
            ->whereIn('f.STATUS', [1, 2])
            ->select(
                'rt.REF_ID as simgos_ref_id',
                'rt.JENIS as simgos_jenis_tarif',
                'b.NAMA as deskripsi_item',
                'rt.JUMLAH as qty',
                'rt.TARIF as harga_satuan',
            )
            ->union(
                // Tindakan yang JENIS = 18 (Farmasi) digabung ke sini
                DB::connection('simgos_pembayaran')
                    ->table('rincian_tagihan as rt')
                    ->join('layanan.tindakan_medis as tm', 'tm.ID', '=', 'rt.REF_ID')
                    ->join('master.tindakan as t', 't.ID', '=', 'tm.TINDAKAN')
                    ->where('rt.TAGIHAN', $simgosTagihanID)
                    ->where('rt.JENIS', 3)
                    ->where('tm.STATUS', 1)
                    ->where('t.JENIS', 18)    // JENIS Farmasi
                    ->where('rt.TARIF', '>', 0)
                    ->select(
                        'rt.REF_ID as simgos_ref_id',
                        DB::raw("4 as simgos_jenis_tarif"), // paksa jadi 4 biar sama dengan farmasi
                        't.NAMA as deskripsi_item',
                        'rt.JUMLAH as qty',
                        'rt.TARIF as harga_satuan',
                    )
            );

        $queryRawatInap = DB::connection('simgos_pembayaran')
            ->table('rincian_tagihan as rt')
            ->join('pendaftaran.kunjungan as k', 'k.NOMOR', '=', 'rt.REF_ID')
            ->leftJoin('master.ruang_kamar_tidur as rkt', 'rkt.ID', '=', 'k.RUANG_KAMAR_TIDUR')
            ->leftJoin('master.ruang_kamar as rk', 'rk.ID', '=', 'rkt.RUANG_KAMAR')
            ->leftJoin('master.referensi as ref', function ($join) {
                $join->on('ref.ID', '=', 'rk.KELAS')->where('ref.JENIS', 19); // Kelas Rawat
            })
            ->where('rt.TAGIHAN', $simgosTagihanID)
            ->where('rt.JENIS', 2) // 2 = Rawat Inap
            ->select(
                'rt.REF_ID as simgos_ref_id',
                'rt.JENIS as simgos_jenis_tarif',

                // 🔥 DI SINI LETAK DB::raw-nya
                DB::raw(
                    "TRIM(CONCAT(
                IFNULL(rkt.TEMPAT_TIDUR, ''),
                ' ',
                IFNULL(ref.DESKRIPSI, '')
            )) as deskripsi_item",
                ),

                'rt.JUMLAH as qty',
                'rt.TARIF as harga_satuan',
            );

        $queryOksigen = DB::connection('simgos_pembayaran')
            ->table('rincian_tagihan as rt')
            ->where('rt.TAGIHAN', $simgosTagihanID)
            ->where('rt.JENIS', 6) // <--- JENIS 6 = OKSIGEN
            ->select(
                'rt.REF_ID as simgos_ref_id',
                'rt.JENIS as simgos_jenis_tarif',
                // Kita beri nama manual karena REF_ID-nya adalah ID Kunjungan, bukan ID Master
                DB::raw("'Pemakaian Oksigen' as deskripsi_item"),
                'rt.JUMLAH as qty',
                'rt.TARIF as harga_satuan',
            );

        // --- Akhir Query UNION ---

        $rincianLengkap = $queryAdmin->union($queryTindakan)->union($queryFarmasi)->union($queryRawatInap)->union($queryOksigen)->get();
        // 6. Masukkan data rincian baru
        $dataDetailUntukInsert = [];
        foreach ($rincianLengkap as $item) {
            $subtotal = $item->qty * $item->harga_satuan;
            $dataDetailUntukInsert[] = [
                'kasir_tagihan_head_id' => $tagihanHead->id,
                'simgos_ref_id' => $item->simgos_ref_id,
                'simgos_jenis_tarif' => $item->simgos_jenis_tarif,
                'deskripsi_item' => $item->deskripsi_item,
                'qty' => $item->qty,
                'harga_satuan' => $item->harga_satuan,
                'subtotal' => $subtotal,
                'nominal_ditanggung_asuransi' => 0,
                'nominal_ditanggung_pasien' => $subtotal,
            ];
        }
        KasirTagihanDetail::insert($dataDetailUntukInsert);

        // 7. Redirect ke halaman rincian LOKAL
        return redirect()->route('kasir.tagihan.lokal', ['id' => $tagihanHead->id, 'jenis_kasir' => $request->jenis_kasir]);
    }

    public function showLokalTagihan(Request $request, $id)
    {
        $tagihanHead = KasirTagihanHead::findOrFail($id);

        $totalSimgosTerkini = DB::connection('simgos_pembayaran')
            ->table('tagihan')
            ->where('ID', $tagihanHead->simgos_tagihan_id)
            ->value('TOTAL');

        $isDataValid = (float) $tagihanHead->total_asli_simgos === (float) $totalSimgosTerkini;

        $tagihanDetail = KasirTagihanDetail::where('kasir_tagihan_head_id', $id)
            ->orderBy('simgos_jenis_tarif')
            ->get();

        $metodeBayar = AppReferensi::where('JENIS', 1)
            ->where('STATUS', true)
            ->get();

        $jenis_kasir = $request->input('jenis_kasir', 1);

        return view('kasir.rincian-lokal', [
            'head' => $tagihanHead,
            'detail' => $tagihanDetail,
            'metodeBayar' => $metodeBayar,
            'jenis_kasir' => $jenis_kasir,
            'isDataValid' => $isDataValid, // Kirim status validasi ke blade
            // 'totalSimgosTerkini' => $totalSimgosTerkini // Opsional: kirim jika ingin menampilkan harga terbaru
        ]);
    }

    /**
     * Menampilkan halaman 'Bagi Tagihan' (mockup Anda)
     */
    public function showBagiTagihan($id)
    {
        $tagihanHead = KasirTagihanHead::findOrFail($id);
        $tagihanDetail = KasirTagihanDetail::where('kasir_tagihan_head_id', $id)->get();

        return view('kasir.bagi-tagihan', [
            'head' => $tagihanHead,
            'detail' => $tagihanDetail,
        ]);
    }

    /**
     * Menyimpan data dari halaman 'Bagi Tagihan'
     */
    public function storeBagiTagihan(Request $request, $id)
    {
        // $id di sini adalah ID dari 'kasir_tagihan_head'
        $tagihanHead = KasirTagihanHead::findOrFail($id);
        $dataAsuransi = $request->input('asuransi', []);

        // Ambil jenis kasir dari form
        $jenisKasir = $request->jenis_kasir;

        // Variabel untuk menghitung total baru
        $totalPasienBaru = 0;
        $totalAsuransiBaru = 0;

        DB::transaction(function () use ($dataAsuransi, &$totalPasienBaru, &$totalAsuransiBaru) {
            foreach ($dataAsuransi as $detail_id => $nominal_asuransi) {
                $nominal_asuransi_bersih = str_replace('.', '', $nominal_asuransi);

                $nominal_asuransi = (float) $nominal_asuransi_bersih;

                $item = KasirTagihanDetail::findOrFail($detail_id);

                // Pastikan nominal asuransi tidak melebihi subtotal
                if ($nominal_asuransi > $item->subtotal) {
                    $nominal_asuransi = $item->subtotal;
                }

                $nominal_pasien = $item->subtotal - $nominal_asuransi;

                // Update database
                $item->update([
                    'nominal_ditanggung_asuransi' => $nominal_asuransi,
                    'nominal_ditanggung_pasien' => $nominal_pasien,
                ]);

                // Tambahkan ke total
                $totalPasienBaru += $nominal_pasien;
                $totalAsuransiBaru += $nominal_asuransi;
            }
        });

        // Kurangi total pasien dengan diskon yang tersimpan di head
        $diskon = $tagihanHead->diskon_simgos ?? 0;

        // Pastikan tidak minus
        $totalBayarPasienNet = max(0, $totalPasienBaru - $diskon);

        // Simpan total yang sudah dihitung ulang ke tabel HEAD
        $tagihanHead->update([
            'total_bayar_pasien' => $totalBayarPasienNet, //Gunakan harga netto setelah diskon
            'total_bayar_asuransi' => $totalAsuransiBaru,
        ]);

        // Kembalikan ke halaman rincian
        return redirect()
            ->route('kasir.tagihan.lokal', ['id' => $id, 'jenis_kasir' => $jenisKasir])
            ->with('success', 'Pembagian tagihan berhasil disimpan!');
    }

    public function storePembayaran(Request $request, $id)
    {
        // Validasi input dasar
        $request->validate([
            'metode_bayar_id' => 'required|integer',
            'nominal_bayar' => 'required|numeric|min:0',
            'jenis_kasir' => 'required|integer',
            // Validasi tambahan jika piutang
            'total_piutang_dipilih' => 'exclude_unless:metode_bayar_id,4|required|numeric|min:1',
            'piutang_item_ids' => 'required_if:metode_bayar_id,4|array',
            'piutang_item_ids.*' => 'integer',
        ]);

        $metodeBayarID = $request->metode_bayar_id;
        $jenisKasir = $request->jenis_kasir;
        $isPiutang = ($metodeBayarID == 4);

        // ======================================================
        // VALIDASI SESI AKTIF
        // ======================================================
        $sesiAktif = KasirSesi::where('status', 'BUKA')
            ->where('jenis_kasir', $jenisKasir)
            ->first();

        if (!$sesiAktif) {
            return redirect()->back()->with(
                'error',
                'Sesi kasir untuk jenis kasir ' . $jenisKasir . ' belum dibuka! Silakan buka sesi kasir sesuai role Anda.'
            );
        }

        // Ambil data tagihan lokal
        $tagihanHead = KasirTagihanHead::findOrFail($id);

        // Cek tagihan sudah lunas / piutang?
        if (in_array($tagihanHead->status_kasir, ['lunas', 'piutang'])) {
            return redirect()
                ->route('kasir.tagihan.lokal', ['id' => $id])
                ->with('info', 'Tagihan ini sudah diproses (' . strtoupper($tagihanHead->status_kasir) . ').');
        }

        // Cek total SIMGOS
        $totalSimgosTerkini = DB::connection('simgos_pembayaran')
            ->table('tagihan')
            ->where('ID', $tagihanHead->simgos_tagihan_id)
            ->value('TOTAL');

        if ((float) $tagihanHead->total_asli_simgos != (float) $totalSimgosTerkini) {
            return redirect()
                ->route('kasir.tagihan.lokal', ['id' => $id])
                ->with('error', 'GAGAL BAYAR: Data SIMGOS telah berubah! Silakan refresh.');
        }

        // Hitung nominal wajib (server-side)
        $nominalWajibBayar = $tagihanHead->total_asli_simgos
            - $tagihanHead->diskon_simgos
            - $tagihanHead->total_bayar_asuransi;

        $nominalFinal = max(0, $nominalWajibBayar);

        // ======================================================
        // HITUNG NOMINAL ASURANSI DARI ITEM YANG DIPILIH
        // ======================================================
        $nominalPiutang = 0;

        if ($isPiutang) {
            // Ambil dari detail tagihan berdasarkan piutang_item_ids yang dikirim
            $nominalPiutang = KasirTagihanDetail::whereIn('id', $request->piutang_item_ids)
                ->where('kasir_tagihan_head_id', $tagihanHead->id)
                ->sum('nominal_ditanggung_asuransi');

            // Validasi server-side: nominal piutang tidak boleh 0
            if ($nominalPiutang <= 0) {
                return redirect()->back()->with(
                    'error',
                    'GAGAL: Nominal piutang asuransi tidak valid. Pastikan item dipilih dengan benar.'
                );
            }
        }

        // dd([
        //     'total_asli_simgos' => $tagihanHead->total_asli_simgos,
        //     'diskon_simgos' => $tagihanHead->diskon_simgos,
        //     'total_bayar_asuransi' => $tagihanHead->total_bayar_asuransi,
        //     'nominalFinal' => $nominalFinal,
        //     'nominalPiutang' => $nominalPiutang,
        //     'nominalBayarPasien' => $nominalFinal - $nominalPiutang,
        //     'isPiutang' => $isPiutang,
        // ]);

        // ======================================================
        // WRAP DALAM TRANSACTION AGAR ATOMIC
        // ======================================================
        DB::transaction(function () use ($request, $tagihanHead, $sesiAktif, $nominalFinal, $nominalPiutang, $isPiutang) {

            if ($isPiutang) {
                // Hitung porsi pasien = total - porsi asuransi
                $nominalBayarPasien = $nominalFinal;

                // Rekap porsi PASIEN ke kasir_pembayaran (jika ada)
                // if ($nominalBayarPasien > 0) {
                KasirPembayaran::create([
                    'kasir_tagihan_head_id' => $tagihanHead->id,
                    'user_id' => Auth::id(),
                    'metode_bayar_id' => $request->metode_bayar_id,
                    'nominal_bayar' => $nominalBayarPasien,
                    'kasir_sesi_id' => $sesiAktif->id,
                    'keterangan' => 'Bayar Tagihan Pasien Asuransi',
                ]);
                // }

                // Simpan porsi ASURANSI ke tabel piutang
                KasirTagihanPiutang::create([
                    'kasir_tagihan_head_id' => $tagihanHead->id,
                    'simgos_tagihan_id' => $tagihanHead->simgos_tagihan_id,
                    'simgos_norm' => $tagihanHead->simgos_norm,
                    'nama_pasien' => $tagihanHead->nama_pasien,
                    'nama_asuransi' => $tagihanHead->nama_asuransi,
                    'total_tagihan_asuransi' => $tagihanHead->total_bayar_asuransi,
                    'nominal_piutang' => $nominalPiutang,
                    'nominal_terbayar' => 0,
                    'nominal_sisa' => $nominalPiutang,
                    'status' => 'outstanding',
                    'user_id' => Auth::id(),
                    'kasir_sesi_id' => $sesiAktif->id,
                ]);

                // Update status tagihan → piutang
                $tagihanHead->update(['status_kasir' => 'piutang']);

            } else {
                // Pembayaran tunai/non-piutang → simpan penuh & langsung lunas
                KasirPembayaran::create([
                    'kasir_tagihan_head_id' => $tagihanHead->id,
                    'user_id' => Auth::id(),
                    'metode_bayar_id' => $request->metode_bayar_id,
                    'nominal_bayar' => $nominalFinal,
                    'kasir_sesi_id' => $sesiAktif->id,
                ]);

                $tagihanHead->update(['status_kasir' => 'lunas']);
            }
        });

        $pesanSukses = $isPiutang
            ? 'Tagihan berhasil diproses sebagai PIUTANG asuransi.'
            : 'Pembayaran berhasil disimpan!';

        return redirect()
            ->route('kasir.tagihan.lokal', [
                'id' => $id,
                'jenis_kasir' => $request->jenis_kasir,
            ])
            ->with('success', $pesanSukses);
    }

    /**
     * Membatalkan pembayaran (Rollback).
     * Data pembayaran di-soft delete, status tagihan kembali jadi 'draft'.
     */
    public function batalPembayaran(Request $request, $id)
    {
        $tagihanHead = KasirTagihanHead::findOrFail($id);
        $jenisKasir = $request->jenis_kasir;

        // 1. Validasi: Hanya bisa batal jika status lunas ATAU piutang
        if (!in_array($tagihanHead->status_kasir, ['lunas', 'piutang', 'outstanding'])) {
            return redirect()
                ->route('kasir.tagihan.lokal', ['id' => $id])
                ->with('error', 'Tagihan ini statusnya belum lunas, tidak perlu dibatalkan.');
        }

        // 2. Cek apakah ini tagihan piutang yang sudah lunas (ada di kasir_pembayaran dengan metode 4)
        $isPiutangSudahLunas = KasirPembayaran::where('kasir_tagihan_head_id', $tagihanHead->id)
            ->where('metode_bayar_id', 4)
            ->exists() && $tagihanHead->status_kasir === 'lunas';

        if ($isPiutangSudahLunas) {
            return redirect()
                ->route('kasir.tagihan.lokal', ['id' => $id])
                ->with('error', 'Tagihan piutang ini sudah lunas. Batalkan lewat menu Piutang terlebih dahulu.');
        }

        // 3. Proses Rollback Database
        DB::transaction(function () use ($tagihanHead) {
            // A. Soft delete semua pembayaran terkait (jika ada)
            KasirPembayaran::where('kasir_tagihan_head_id', $tagihanHead->id)->delete();

            // B. Jika status piutang (belum lunas, belum ada cicilan) → hapus record piutang
            if ($tagihanHead->status_kasir === 'piutang' || $tagihanHead->status_kasir === 'outstanding') {
                $piutangIds = KasirTagihanPiutang::where('kasir_tagihan_head_id', $tagihanHead->id)
                    ->pluck('id');

                KasirPiutangPembayaran::whereIn('kasir_tagihan_piutang_id', $piutangIds)->delete();
                KasirTagihanPiutang::whereIn('id', $piutangIds)->delete();
            }

            // C. Kembalikan status header menjadi 'draft'
            $tagihanHead->update(['status_kasir' => 'draft']);
        });

        return redirect()
            ->route('kasir.tagihan.lokal', ['id' => $id, 'jenis_kasir' => $jenisKasir])
            ->with('success', 'Pembayaran BERHASIL DIBATALKAN. Silakan tekan tombol [Refresh] untuk menarik data revisi dari SIMGOS.');
    }
    /**
     * Mencetak Kuitansi Pasien atau Asuransi dalam format PDF.
     * VERSI DENGAN AGREGRASI DETAIL TINDAKAN.
     */
    public function cetakKuitansi(Request $request, $id)
    {
        $jenis_kasir = $request->input('jenis_kasir');
        $jenisList = [
            1 => 'Rawat Jalan',
            2 => 'IGD',
            3 => 'Rawat Inap',
            4 => 'Laboratorium',
            5 => 'Radiologi',
        ];
        $jenis_kasir_text = $jenisList[$jenis_kasir] ?? 'Tidak diketahui';

        // 1. Ambil data header tagihan
        $tagihanHead = KasirTagihanHead::findOrFail($id);

        // 2. Tentukan Tipe Kuitansi
        $routeName = Route::currentRouteName();
        $tipeKuitansi = $routeName == 'kuitansi.cetak.pasien' ? 'Pasien' : 'Asuransi';

        // 3. Ambil data detail tagihan LOKAL kita
        $tagihanDetail = KasirTagihanDetail::where('kasir_tagihan_head_id', $id)->get();

        // 4. Lakukan Agregasi (Rekap) - Logika Baru
        $rekapData = [];
        $grandTotal = 0;

        // Siapkan array untuk menampung subtotal per kategori
        $subtotals = [
            'Administrasi' => 0,
            'Akomodasi' => 0, //JENIS = 2 BARU DITAMBAH
            'Pemeriksaan Dokter' => 0, // Dulu Konsultasi (JENIS=3)
            'Pemeriksaan Radiologi' => 0, // JENIS=7
            'Pemeriksaan Lab' => 0, // JENIS=8
            'Tindakan Dokter' => 0, // Default untuk JENIS=3 lainnya
            'Farmasi' => 0,
            'Gas Medis' => 0,
            // Tindakan Keperawatan akan ditangani terpisah
        ];
        $tindakanKeperawatan = []; // Array untuk item keperawatan

        foreach ($tagihanDetail as $item) {
            $nominal = $tipeKuitansi == 'Pasien' ? $item->nominal_ditanggung_pasien : $item->nominal_ditanggung_asuransi;

            // Hanya proses jika nominal > 0
            if ($nominal <= 0) {
                continue;
            }

            // --- Logika Pengelompokan ---
            switch ($item->simgos_jenis_tarif) {
                case 1: // Administrasi
                    $subtotals['Administrasi'] += $nominal;
                    break;

                case 2: // 🔥 AKOMODASI (RAWAT INAP)
                    $subtotals['Akomodasi'] += $nominal;
                    break;

                case 4: // Farmasi
                    $subtotals['Farmasi'] += $nominal; //Penggantian Biaya Obat ke Farmasi
                    break;

                case 3: // Tindakan Medis - PERLU DICEK JENISNYA
                    // Query tambahan untuk mendapatkan JENIS tindakan dari master.tindakan
                    $tindakanInfo = DB::connection('simgos_pembayaran') // Koneksi bebas, asal bisa join
                        ->table('layanan.tindakan_medis as tm')
                        ->join('master.tindakan as t', 't.ID', '=', 'tm.TINDAKAN')
                        ->where('tm.ID', $item->simgos_ref_id) // Gunakan ref_id dari detail lokal
                        ->select('t.JENIS as jenis_tindakan', 't.NAMA as nama_tindakan')
                        ->first(); // Ambil satu baris

                    if ($tindakanInfo) {
                        switch ($tindakanInfo->jenis_tindakan) {
                            case 3: // Konsultasi
                                $subtotals['Pemeriksaan Dokter'] += $nominal;
                                break;
                            case 7: // Radiologi
                                $subtotals['Pemeriksaan Radiologi'] += $nominal;
                                break;
                            case 8: // Laboratorium
                                $subtotals['Pemeriksaan Lab'] += $nominal;
                                break;
                            case 5: // Keperawatan - DIGABUNG JIKA NAMANYA SAMA
                                $nama_tindakan = $tindakanInfo->nama_tindakan;

                                // Cek apakah tindakan ini sudah ada di array sebelumnya
                                if (!isset($tindakanKeperawatan[$nama_tindakan])) {
                                    // Jika belum ada, buat baru
                                    $tindakanKeperawatan[$nama_tindakan] = [
                                        'uraian' => $nama_tindakan,
                                        'qty' => 0, // Tambahkan counter Qty
                                        'subtotal' => 0,
                                    ];
                                }

                                // Tambahkan Qty dan nominalnya
                                $tindakanKeperawatan[$nama_tindakan]['qty'] += 1;
                                $tindakanKeperawatan[$nama_tindakan]['subtotal'] += $nominal;
                                break;
                            case 11:
                                $subtotals['Akomodasi'] += $nominal;
                                break;
                            default:
                                // Jenis tindakan lain (1, 2, 4, 6, 10, dll)
                                $subtotals['Tindakan Dokter'] += $nominal;
                                break;
                        }
                    } else {
                        // Jika info tindakan tidak ditemukan (jarang terjadi), masukkan ke default
                        $subtotals['Tindakan Dokter'] += $nominal;
                    }
                    break;

                case 6: // Oksigen
                    $subtotals['Gas Medis'] += $nominal;
                    break;

                default:
                    // Jika ada jenis tarif lain, bisa ditambahkan di sini
                    break;
            }
            $grandTotal += $nominal; // Tambahkan ke grand total
        }

        // 5. Format $rekapData untuk View
        // Gabungkan subtotal yang dikelompokkan
        foreach ($subtotals as $uraian => $subtotal) {
            if ($subtotal > 0) {
                $rekapData[] = ['uraian' => $uraian, 'subtotal' => $subtotal];
            }
        }
        // Tambahkan item keperawatan (jika ada), reset index dengan array_values
        $rekapData = array_merge($rekapData, array_values($tindakanKeperawatan));
        // dd($rekapData);

        // 6. Siapkan data untuk dikirim ke View
        $dataUntukView = [
            'head' => $tagihanHead,
            'rekap' => $rekapData,
            'grandTotal' => $grandTotal,
            'tipeKuitansi' => $tipeKuitansi,
            'namaKasir' => Auth::user()->nama,
            'jenis_kasir_text' => $jenis_kasir_text,
        ];

        // 7. Load View PDF dan kirim data
        $pdf = PDF::loadView('reports.kuitansi', $dataUntukView);
        $pdf->setPaper([0, 0, 612.28, 396.85], 'portrait');

        // 8. Tampilkan PDF
        return $pdf->stream('kuitansi-' . $tagihanHead->simgos_tagihan_id . '.pdf');
    }

    public function cetakKuitansiFull(Request $request, $id)
    {
        $jenis_kasir = $request->input('jenis_kasir');
        $jenisList = [
            1 => 'Rawat Jalan',
            2 => 'IGD',
            3 => 'Rawat Inap',
            4 => 'Laboratorium',
            5 => 'Radiologi',
        ];
        $jenis_kasir_text = $jenisList[$jenis_kasir] ?? 'Tidak diketahui';

        // 1. Ambil data header tagihan
        $tagihanHead = KasirTagihanHead::findOrFail($id);

        // 2. Tentukan Tipe Kuitansi
        $routeName = Route::currentRouteName();
        $tipeKuitansi = $routeName == 'kuitansi.cetak.pasien.full' ? 'Pasien' : 'Asuransi';

        // 3. Ambil data detail tagihan LOKAL kita
        $tagihanDetail = KasirTagihanDetail::where('kasir_tagihan_head_id', $id)->get();

        // 4. Lakukan Agregasi (Rekap) - Logika Baru
        $rekapData = [];
        $grandTotal = 0;

        // Siapkan array untuk menampung subtotal per kategori
        $subtotals = [
            'Administrasi' => 0,
            'Akomodasi' => 0, //JENIS = 2 BARU DITAMBAH
            'Pemeriksaan Dokter' => 0, // Dulu Konsultasi (JENIS=3)
            'Pemeriksaan Radiologi' => 0, // JENIS=7
            'Pemeriksaan Lab' => 0, // JENIS=8
            'Tindakan Dokter' => 0, // Default untuk JENIS=3 lainnya
            'Farmasi' => 0,
            'Gas Medis' => 0,
            // Tindakan Keperawatan akan ditangani terpisah
        ];
        $tindakanKeperawatan = []; // Array untuk item keperawatan

        foreach ($tagihanDetail as $item) {
            $nominal = $tipeKuitansi == 'Pasien' ? $item->nominal_ditanggung_pasien : $item->nominal_ditanggung_asuransi;

            // Hanya proses jika nominal > 0
            if ($nominal <= 0) {
                continue;
            }

            // --- Logika Pengelompokan ---
            switch ($item->simgos_jenis_tarif) {
                case 1: // Administrasi
                    $subtotals['Administrasi'] += $nominal;
                    break;

                case 2: // 🔥 AKOMODASI (RAWAT INAP)
                    $subtotals['Akomodasi'] += $nominal;
                    break;

                case 4: // Farmasi
                    $subtotals['Farmasi'] += $nominal; //Penggantian Biaya Obat ke Farmasi
                    break;

                case 3: // Tindakan Medis - PERLU DICEK JENISNYA
                    // Query tambahan untuk mendapatkan JENIS tindakan dari master.tindakan
                    $tindakanInfo = DB::connection('simgos_pembayaran') // Koneksi bebas, asal bisa join
                        ->table('layanan.tindakan_medis as tm')
                        ->join('master.tindakan as t', 't.ID', '=', 'tm.TINDAKAN')
                        ->where('tm.ID', $item->simgos_ref_id) // Gunakan ref_id dari detail lokal
                        ->select('t.JENIS as jenis_tindakan', 't.NAMA as nama_tindakan')
                        ->first(); // Ambil satu baris

                    if ($tindakanInfo) {
                        switch ($tindakanInfo->jenis_tindakan) {
                            case 3: // Konsultasi
                                $subtotals['Pemeriksaan Dokter'] += $nominal;
                                break;
                            case 7: // Radiologi
                                $subtotals['Pemeriksaan Radiologi'] += $nominal;
                                break;
                            case 8: // Laboratorium
                                $subtotals['Pemeriksaan Lab'] += $nominal;
                                break;
                            case 5: // Keperawatan - DIGABUNG JIKA NAMANYA SAMA
                                $nama_tindakan = $tindakanInfo->nama_tindakan;

                                // Cek apakah tindakan ini sudah ada di array sebelumnya
                                if (!isset($tindakanKeperawatan[$nama_tindakan])) {
                                    // Jika belum ada, buat baru
                                    $tindakanKeperawatan[$nama_tindakan] = [
                                        'uraian' => $nama_tindakan,
                                        'qty' => 0, // Tambahkan counter Qty
                                        'subtotal' => 0,
                                    ];
                                }

                                // Tambahkan Qty dan nominalnya
                                $tindakanKeperawatan[$nama_tindakan]['qty'] += 1;
                                $tindakanKeperawatan[$nama_tindakan]['subtotal'] += $nominal;
                                break;
                            case 11:
                                $subtotals['Akomodasi'] += $nominal;
                                break;
                            default:
                                // Jenis tindakan lain (1, 2, 4, 6, 10, dll)
                                $subtotals['Tindakan Dokter'] += $nominal;
                                break;
                        }
                    } else {
                        // Jika info tindakan tidak ditemukan (jarang terjadi), masukkan ke default
                        $subtotals['Tindakan Dokter'] += $nominal;
                    }
                    break;

                case 6: // Oksigen
                    $subtotals['Gas Medis'] += $nominal;
                    break;

                default:
                    // Jika ada jenis tarif lain, bisa ditambahkan di sini
                    break;
            }
            $grandTotal += $nominal; // Tambahkan ke grand total
        }

        // 5. Format $rekapData untuk View
        // Gabungkan subtotal yang dikelompokkan
        foreach ($subtotals as $uraian => $subtotal) {
            if ($subtotal > 0) {
                $rekapData[] = ['uraian' => $uraian, 'subtotal' => $subtotal];
            }
        }
        // Tambahkan item keperawatan (jika ada), reset index dengan array_values
        $rekapData = array_merge($rekapData, array_values($tindakanKeperawatan));
        // dd($rekapData);

        // 6. Siapkan data untuk dikirim ke View
        $dataUntukView = [
            'head' => $tagihanHead,
            'rekap' => $rekapData,
            'grandTotal' => $grandTotal,
            'tipeKuitansi' => $tipeKuitansi,
            'namaKasir' => Auth::user()->nama,
            'jenis_kasir_text' => $jenis_kasir_text,
        ];

        // 7. Load View PDF dan kirim data
        $pdf = PDF::loadView('reports.kuitansi', $dataUntukView);
        // $pdf->setPaper([0, 0, 612.28, 396.85], 'portrait');
        $pdf->setPaper([0, 0, 612.28, 792], 'portrait');


        // 8. Tampilkan PDF
        return $pdf->stream('kuitansi-' . $tagihanHead->simgos_tagihan_id . '.pdf');
    }

    // $pdf->setPaper([0, 0, 612.28, 792], 'portrait');
    //
    /**
     * Menyegarkan (refresh) data rincian tagihan dari SIMGOS.
     * Hanya berjalan jika status kasir masih 'draft'.
     */
    public function refreshTagihanSimgos(Request $request, $id)
    {
        // $id adalah kasir_tagihan_head_id
        // 1. Cari Tagihan Head Lokal
        $tagihanHead = KasirTagihanHead::findOrFail($id);

        // 2. PERIKSA STATUS: HANYA JALANKAN JIKA 'draft'
        if ($tagihanHead->status_kasir != 'draft') {
            return redirect()
                ->route('kasir.tagihan.lokal', ['id' => $id])
                ->with('error', 'Tagihan ini sudah lunas, data tidak bisa di-refresh.');
        }

        // --- Lanjutkan proses refresh (mirip prosesDanBukaTagihan) ---
        $simgosTagihanID = $tagihanHead->simgos_tagihan_id; // Ambil ID SIMGOS dari head
        $norm = $tagihanHead->simgos_norm;

        // 🔥 AMBIL NAMA ASURANSI TERBARU DARI SIMGOS
        $namaAsuransiBaru = $this->ambilNamaAsuransiSimgos($simgosTagihanID) ?? '-';

        $dataPasien = DB::connection('simgos_master') // sesuaikan nama connection
            ->table('pasien')
            ->where('NORM', $norm)
            ->select(
                'NAMA',
                'GELAR_DEPAN',
                'GELAR_BELAKANG',
            )
            ->first();

        // 🔥 AMBIL NAMA PASIEN TERBARU DARI SIMGOS (dengan gelar)
        $namaPasienBaru = $tagihanHead->nama_pasien; // fallback: nama lama
        if ($dataPasien) {
            $namaPasienBaru = trim(
                implode(' ', array_filter([
                    $dataPasien->GELAR_DEPAN,
                    $dataPasien->NAMA,
                    $dataPasien->GELAR_BELAKANG,
                ]))
            );
        }

        // 🔥 AMBIL NAMA DOKTER DPJP TERBARU DARI SIMGOS
        $namaDokterBaru = DB::connection('simgos_pembayaran')
            ->table('tagihan as t')
            ->join('tagihan_pendaftaran as tp', 'tp.TAGIHAN', '=', 't.ID')
            ->join('pendaftaran.kunjungan as k', 'tp.PENDAFTARAN', '=', 'k.NOPEN')
            ->join('master.dokter as d', 'k.DPJP', '=', 'd.ID')
            ->join('master.pegawai as p', 'd.NIP', '=', 'p.NIP')
            ->where('t.ID', $simgosTagihanID)
            ->where('tp.STATUS', 1)
            ->where('tp.UTAMA', 1)
            ->value(DB::raw("CONCAT_WS(' ', p.GELAR_DEPAN, p.NAMA, p.GELAR_BELAKANG)"));

        // --- [BARU] AMBIL DATA DISKON TERBARU DARI SIMGOS ---
        // A. Ambil Diskon RS
        $diskonRSData = DB::connection('simgos_pembayaran')->table('diskon')->where('TAGIHAN', $simgosTagihanID)->first();

        $totalDiskonRS = 0;
        if ($diskonRSData) {
            $totalDiskonRS = ($diskonRSData->ADMINISTRASI ?? 0) + ($diskonRSData->AKOMODASI ?? 0) + ($diskonRSData->SARANA_NON_AKOMODASI ?? 0) + ($diskonRSData->PARAMEDIS ?? 0);
        }

        // B. Ambil Diskon Dokter
        $totalDiskonDokter = DB::connection('simgos_pembayaran')->table('diskon_dokter')->where('TAGIHAN', $simgosTagihanID)->sum('TOTAL');

        // C. Total Gabungan Diskon
        $totalDiskonSimgos = $totalDiskonRS + $totalDiskonDokter;
        // --- [AKHIR LOGIKA DISKON] ---

        // 3. HAPUS detail lama (Wipe)
        KasirTagihanDetail::where('kasir_tagihan_head_id', $tagihanHead->id)->delete();

        // 4. Bangun "Resep Snapshot" (Replace)
        // --- Query UNION Anda ---
        $queryAdmin = DB::connection('simgos_pembayaran')
            ->table('rincian_tagihan as rt')
            ->join('master.referensi as ref', function ($join) {
                $join->on('ref.ID', '=', 'rt.JENIS')->where('ref.JENIS', 30);
            })
            ->where('rt.TAGIHAN', $simgosTagihanID)
            ->where('rt.JENIS', 1)
            // Hanya mengambil harga admin yang lebih 0
            ->where('rt.TARIF', '>', 0)
            ->select('rt.REF_ID as simgos_ref_id', 'rt.JENIS as simgos_jenis_tarif', DB::raw("'Administrasi' as deskripsi_item"), 'rt.JUMLAH as qty', 'rt.TARIF as harga_satuan');

        $queryTindakan = DB::connection('simgos_pembayaran')->table('rincian_tagihan as rt')->join('layanan.tindakan_medis as tm', 'tm.ID', '=', 'rt.REF_ID')->join('master.tindakan as t', 't.ID', '=', 'tm.TINDAKAN')->where('rt.TAGIHAN', $simgosTagihanID)->where('rt.JENIS', 3)->where('tm.STATUS', 1)->select('rt.REF_ID as simgos_ref_id', 'rt.JENIS as simgos_jenis_tarif', 't.NAMA as deskripsi_item', 'rt.JUMLAH as qty', 'rt.TARIF as harga_satuan');

        $queryFarmasi = DB::connection('simgos_pembayaran')
            ->table('rincian_tagihan as rt')
            ->join('layanan.farmasi as f', 'f.ID', '=', 'rt.REF_ID')
            ->join('inventory.barang as b', 'b.ID', '=', 'f.FARMASI')
            ->where('rt.TAGIHAN', $simgosTagihanID)
            ->where('rt.JENIS', 4)
            ->whereIn('f.STATUS', [1, 2])
            ->select('rt.REF_ID as simgos_ref_id', 'rt.JENIS as simgos_jenis_tarif', 'b.NAMA as deskripsi_item', 'rt.JUMLAH as qty', 'rt.TARIF as harga_satuan');

        $queryRawatInap = DB::connection('simgos_pembayaran')
            ->table('rincian_tagihan as rt')
            ->join('pendaftaran.kunjungan as k', 'k.NOMOR', '=', 'rt.REF_ID')
            ->leftJoin('master.ruang_kamar_tidur as rkt', 'rkt.ID', '=', 'k.RUANG_KAMAR_TIDUR')
            ->leftJoin('master.ruang_kamar as rk', 'rk.ID', '=', 'rkt.RUANG_KAMAR')
            ->leftJoin('master.referensi as ref', function ($join) {
                $join->on('ref.ID', '=', 'rk.KELAS')->where('ref.JENIS', 19);
            })
            ->where('rt.TAGIHAN', $simgosTagihanID)
            ->where('rt.JENIS', 2) // 2 = Rawat Inap
            ->select('rt.REF_ID as simgos_ref_id', 'rt.JENIS as simgos_jenis_tarif', DB::raw("TRIM(CONCAT(IFNULL(rkt.TEMPAT_TIDUR, ''), ' ', IFNULL(ref.DESKRIPSI, ''))) as deskripsi_item"), 'rt.JUMLAH as qty', 'rt.TARIF as harga_satuan');

        $queryOksigen = DB::connection('simgos_pembayaran')->table('rincian_tagihan as rt')->where('rt.TAGIHAN', $simgosTagihanID)->where('rt.JENIS', 6)->select('rt.REF_ID as simgos_ref_id', 'rt.JENIS as simgos_jenis_tarif', DB::raw("'Pemakaian Oksigen' as deskripsi_item"), 'rt.JUMLAH as qty', 'rt.TARIF as harga_satuan');
        // --- Akhir Query UNION ---

        $rincianLengkap = $queryAdmin->union($queryTindakan)->union($queryFarmasi)->union($queryRawatInap)->union($queryOksigen)->get();

        // 5. Masukkan data rincian baru
        $dataDetailUntukInsert = [];
        $totalAsliBaru = 0; // Hitung ulang total asli
        foreach ($rincianLengkap as $item) {
            $subtotal = $item->qty * $item->harga_satuan;
            $dataDetailUntukInsert[] = [
                'kasir_tagihan_head_id' => $tagihanHead->id,
                'simgos_ref_id' => $item->simgos_ref_id,
                'simgos_jenis_tarif' => $item->simgos_jenis_tarif,
                'deskripsi_item' => $item->deskripsi_item,
                'qty' => $item->qty,
                'harga_satuan' => $item->harga_satuan,
                'subtotal' => $subtotal,
                'nominal_ditanggung_asuransi' => 0, // Reset pembagian
                'nominal_ditanggung_pasien' => $subtotal, // Reset pembagian
            ];
            $totalAsliBaru += $subtotal; // Akumulasi total baru
        }
        KasirTagihanDetail::insert($dataDetailUntukInsert);

        // 6. Update total asli di header
        // $tagihanHead->update(['total_asli_simgos' => $totalAsliBaru]);
        $tagihanHead->update([
            'nama_pasien' => $namaPasienBaru,
            'nama_asuransi' => $namaAsuransiBaru,
            'nama_dokter' => $namaDokterBaru ?? $tagihanHead->nama_dokter, // Update nama dokter jika ada
            'total_asli_simgos' => $totalAsliBaru,
            'diskon_simgos' => $totalDiskonSimgos, // Update field diskon
            'total_bayar_asuransi' => 0,
            // Hitung Netto: Total Asli - Diskon
            'total_bayar_pasien' => max(0, $totalAsliBaru - $totalDiskonSimgos),
        ]);

        // 7. Redirect kembali ke halaman rincian dengan pesan sukses
        return redirect()
            ->route('kasir.tagihan.lokal', ['id' => $id, 'jenis_kasir' => $request->jenis_kasir])
            ->with('success', 'Data tagihan berhasil di-refresh dari SIMGOS.');
    }

    public function cetakRincianAsuransi(Request $request, $id)
    {
        $jenis_kasir = $request->input('jenis_kasir');
        $jenisList = [
            1 => 'Rawat Jalan',
            2 => 'IGD',
            3 => 'Rawat Inap',
            4 => 'Laboratorium',
            5 => 'Radiologi',
        ];
        $jenis_kasir_text = $jenisList[$jenis_kasir] ?? 'Tidak diketahui';

        // 1. Header Tagihan
        $tagihanHead = KasirTagihanHead::findOrFail($id);

        // 2. Tipe kuitansi
        $tipeKuitansi = 'Asuransi';

        // 3. Ambil detail yang DITANGGUNG ASURANSI
        $detail = KasirTagihanDetail::where('kasir_tagihan_head_id', $id)->where('nominal_ditanggung_asuransi', '>', 0)->get();

        $jenisTarifList = [
            1 => 'Administrasi',
            2 => 'Akomodasi',
            3 => 'Pemeriksaan Dokter',
            4 => 'Farmasi',
            6 => 'Gas Medis / Oksigen',
            7 => 'Pemeriksaan Radiologi',
            8 => 'Pemeriksaan Lab',
        ];

        $detailByJenis = [];
        $grandTotal = 0;

        foreach ($detail as $item) {
            // ✅ YANG BENAR
            $dibayarAsuransi = $item->nominal_ditanggung_asuransi;
            $jenis = $item->simgos_jenis_tarif;

            // --- KHUSUS TINDAKAN MEDIS ---
            if ($jenis == 3) {
                $tindakanInfo = DB::connection('simgos_pembayaran')->table('layanan.tindakan_medis as tm')->join('master.tindakan as t', 't.ID', '=', 'tm.TINDAKAN')->where('tm.ID', $item->simgos_ref_id)->select('t.JENIS')->first();

                if ($tindakanInfo) {
                    switch ($tindakanInfo->JENIS) {
                        case 7:
                            $jenis = 7;
                            break;
                        case 8:
                            $jenis = 8;
                            break;
                        case 5:
                            $jenis = 'keperawatan';
                            $jenisTarifList['keperawatan'] = 'Tindakan Keperawatan';
                            break;
                        default:
                            $jenis = 3;
                            break;
                    }
                }
            }

            $detailByJenis[$jenis][] = [
                'uraian' => $item->deskripsi_item,
                'qty' => $item->qty,
                'harga' => $item->harga_satuan,
                'subtotal' => $item->subtotal,
                // ✅ ASURANSI
                'dibayar' => $dibayarAsuransi,
            ];

            // ✅ GRAND TOTAL ASURANSI
            $grandTotal += $dibayarAsuransi;
        }

        // Urutkan $detailByJenis berdasarkan nama jenis tarif (abjad)
        uksort($detailByJenis, function ($a, $b) use ($jenisTarifList) {
            $namaA = $jenisTarifList[$a] ?? 'ZZZ'; // Kalau tidak ada, taruh di akhir
            $namaB = $jenisTarifList[$b] ?? 'ZZZ';
            return strcmp($namaA, $namaB);
        });

        $dataUntukView = [
            'head' => $tagihanHead,
            'detailByJenis' => $detailByJenis,
            'grandTotal' => $grandTotal,
            'tipeKuitansi' => $tipeKuitansi,
            'namaKasir' => Auth::user()->nama,
            'jenis_kasir_text' => $jenis_kasir_text,
            'jenisTarifList' => $jenisTarifList,
        ];

        $pdf = PDF::loadView('reports.rincian', $dataUntukView);
        $pdf->setPaper([0, 0, 612.28, 842], 'portrait');

        return $pdf->stream('rincian-asuransi-' . $tagihanHead->simgos_tagihan_id . '.pdf');
    }

    public function cetakRincianPasien(Request $request, $id)
    {
        $jenis_kasir = $request->input('jenis_kasir');
        $jenisList = [
            1 => 'Rawat Jalan',
            2 => 'IGD',
            3 => 'Rawat Inap',
            4 => 'Laboratorium',
            5 => 'Radiologi',
        ];
        $jenis_kasir_text = $jenisList[$jenis_kasir] ?? 'Tidak diketahui';

        // 1. Header Tagihan
        $tagihanHead = KasirTagihanHead::findOrFail($id);

        // 2. Tipe kuitansi otomatis Pasien
        $tipeKuitansi = 'Pasien';

        // 3. Ambil detail HANYA yang dibayar pasien
        $detail = KasirTagihanDetail::where('kasir_tagihan_head_id', $id)->where('nominal_ditanggung_pasien', '>', 0)->get();

        // 4. Daftar nama jenis tarif
        $jenisTarifList = [
            1 => 'Administrasi',
            2 => 'Akomodasi',
            3 => 'Pemeriksaan Dokter', // default jika tidak ditemukan subjenis
            4 => 'Farmasi',
            6 => 'Gas Medis / Oksigen',
            7 => 'Pemeriksaan Radiologi',
            8 => 'Pemeriksaan Lab',
        ];

        $detailByJenis = [];
        $grandTotal = 0;

        foreach ($detail as $item) {
            $dibayarPasien = $item->nominal_ditanggung_pasien;
            $jenis = $item->simgos_jenis_tarif;

            // --- KHUSUS JENIS 3 (TINDAKAN MEDIS) ---
            if ($jenis == 3) {
                // Query untuk mencari jenis tindakan (sama seperti di cetakKuitansi)
                $tindakanInfo = DB::connection('simgos_pembayaran')->table('layanan.tindakan_medis as tm')->join('master.tindakan as t', 't.ID', '=', 'tm.TINDAKAN')->where('tm.ID', $item->simgos_ref_id)->select('t.JENIS as jenis_tindakan', 't.NAMA as nama_tindakan')->first();

                if ($tindakanInfo) {
                    switch ($tindakanInfo->jenis_tindakan) {
                        case 3: // Konsultasi → Pemeriksaan Dokter
                            $jenis = 3;
                            break;

                        case 7: // Radiologi
                            $jenis = 7;
                            break;

                        case 8: // Lab
                            $jenis = 8;
                            break;

                        case 5: // Keperawatan → MASUKKAN sebagai grup baru "Keperawatan"
                            $jenis = 'keperawatan';
                            $jenisTarifList['keperawatan'] = 'Tindakan Keperawatan';
                            break;

                        default:
                            // jenis tindakan lain → fallback ke Pemeriksaan Dokter
                            $jenis = 3;
                            break;
                    }
                }
            }

            // ---- MASUKKAN KE GRUP ----
            $detailByJenis[$jenis][] = [
                'uraian' => $item->deskripsi_item,
                'qty' => $item->qty,
                'harga' => $item->harga_satuan,
                'subtotal' => $item->subtotal,
                'dibayar' => $dibayarPasien,
            ];

            $grandTotal += $dibayarPasien;
        }

        // Urutkan $detailByJenis berdasarkan nama jenis tarif (abjad)
        // Urutkan $detailByJenis berdasarkan nama jenis tarif (abjad)
        uksort($detailByJenis, function ($a, $b) use ($jenisTarifList) {
            $namaA = $jenisTarifList[$a] ?? 'ZZZ'; // Kalau tidak ada, taruh di akhir
            $namaB = $jenisTarifList[$b] ?? 'ZZZ';
            return strcmp($namaA, $namaB);
        });

        // 6. Data untuk view
        $dataUntukView = [
            'head' => $tagihanHead,
            'detailByJenis' => $detailByJenis,
            'grandTotal' => $grandTotal,
            'tipeKuitansi' => $tipeKuitansi,
            'namaKasir' => Auth::user()->nama,
            'jenis_kasir_text' => $jenis_kasir_text,
            'jenisTarifList' => $jenisTarifList,
        ];

        // 7. Load PDF
        $pdf = PDF::loadView('reports.rincian', $dataUntukView);
        $pdf->setPaper([0, 0, 612.28, 842], 'portrait');

        return $pdf->stream('rincian-pasien-' . $tagihanHead->simgos_tagihan_id . '.pdf');
    }

    public function cetakRincianGabungan(Request $request, $id)
    {
        $jenis_kasir = $request->input('jenis_kasir');
        $jenisList = [
            1 => 'Rawat Jalan',
            2 => 'IGD',
            3 => 'Rawat Inap',
            4 => 'Laboratorium',
            5 => 'Radiologi',
        ];
        $jenis_kasir_text = $jenisList[$jenis_kasir] ?? 'Tidak diketahui';

        $tagihanHead = KasirTagihanHead::findOrFail($id);
        $tipeKuitansi = 'Asuransi';

        // Ambil SEMUA detail (bukan hanya yang ditanggung asuransi)
        $detail = KasirTagihanDetail::where('kasir_tagihan_head_id', $id)->get();

        $jenisTarifList = [
            1 => 'Administrasi',
            2 => 'Akomodasi',
            3 => 'Pemeriksaan Dokter',
            4 => 'Farmasi',
            6 => 'Gas Medis / Oksigen',
            7 => 'Pemeriksaan Radiologi',
            8 => 'Pemeriksaan Lab',
        ];

        $detailByJenis = [];
        $grandTotalHarga = 0;
        $grandTotalAsuransi = 0;
        $grandTotalSelisih = 0;

        foreach ($detail as $item) {
            $jenis = $item->simgos_jenis_tarif;
            $hargaTotal = $item->subtotal;
            $ditanggung = $item->nominal_ditanggung_asuransi ?? 0;
            $selisih = $hargaTotal - $ditanggung;

            // Khusus tindakan medis
            if ($jenis == 3) {
                $tindakanInfo = DB::connection('simgos_pembayaran')
                    ->table('layanan.tindakan_medis as tm')
                    ->join('master.tindakan as t', 't.ID', '=', 'tm.TINDAKAN')
                    ->where('tm.ID', $item->simgos_ref_id)
                    ->select('t.JENIS')
                    ->first();

                if ($tindakanInfo) {
                    switch ($tindakanInfo->JENIS) {
                        case 7:
                            $jenis = 7;
                            break;
                        case 8:
                            $jenis = 8;
                            break;
                        case 5:
                            $jenis = 'keperawatan';
                            $jenisTarifList['keperawatan'] = 'Tindakan Keperawatan';
                            break;
                        default:
                            $jenis = 3;
                            break;
                    }
                }
            }

            $detailByJenis[$jenis][] = [
                'uraian' => $item->deskripsi_item,
                'qty' => $item->qty,
                'harga' => $item->harga_satuan,
                'subtotal' => $hargaTotal,
                'asuransi' => $ditanggung,
                'selisih' => $selisih,
            ];

            $grandTotalHarga += $hargaTotal;
            $grandTotalAsuransi += $ditanggung;
            $grandTotalSelisih += $selisih;
        }

        // Urutkan per abjad nama jenis tarif
        uksort($detailByJenis, function ($a, $b) use ($jenisTarifList) {
            $namaA = $jenisTarifList[$a] ?? 'ZZZ';
            $namaB = $jenisTarifList[$b] ?? 'ZZZ';
            return strcmp($namaA, $namaB);
        });

        $dataUntukView = [
            'head' => $tagihanHead,
            'detailByJenis' => $detailByJenis,
            'grandTotalHarga' => $grandTotalHarga,
            'grandTotalAsuransi' => $grandTotalAsuransi,
            'grandTotalSelisih' => $grandTotalSelisih,
            'tipeKuitansi' => $tipeKuitansi,
            'namaKasir' => Auth::user()->nama,
            'jenis_kasir_text' => $jenis_kasir_text,
            'jenisTarifList' => $jenisTarifList,
        ];

        $pdf = PDF::loadView('reports.rincian-gabungan', $dataUntukView);
        $pdf->setPaper([0, 0, 842, 595], 'landscape'); // landscape karena kolom banyak
        return $pdf->stream('rincian-perbandingan-' . $tagihanHead->simgos_tagihan_id . '.pdf');
    }

    public function cetakRincianLab(Request $request, $id)
    {
        $jenis_kasir = $request->input('jenis_kasir');
        $jenisList = [
            1 => 'Rawat Jalan',
            2 => 'IGD',
            3 => 'Rawat Inap',
            4 => 'Laboratorium',
            5 => 'Radiologi',
        ];
        $jenis_kasir_text = $jenisList[$jenis_kasir] ?? 'Tidak diketahui';

        // 1. Header Tagihan
        $tagihanHead = KasirTagihanHead::findOrFail($id);

        // 2. Tipe rincian
        $tipeKuitansi = 'Rincian Lab';

        // 3. Ambil SEMUA detail (pasien + asuransi), TANPA filter nominal
        $detail = KasirTagihanDetail::where('kasir_tagihan_head_id', $id)->get();

        // 4. Jenis tarif (khusus lab)
        $jenisTarifList = [
            8 => 'Pemeriksaan Lab',
        ];

        $detailByJenis = [];
        $grandTotal = 0;

        foreach ($detail as $item) {
            $jenis = $item->simgos_jenis_tarif;

            // --- FILTER KHUSUS LAB ---
            if ($jenis == 3) {
                // Cek apakah tindakan medis ini LAB
                $tindakanInfo = DB::connection('simgos_pembayaran')
                    ->table('layanan.tindakan_medis as tm')
                    ->join('master.tindakan as t', 't.ID', '=', 'tm.TINDAKAN')
                    ->where('tm.ID', $item->simgos_ref_id)
                    ->select('t.JENIS as jenis_tindakan')
                    ->first();

                if (!$tindakanInfo || $tindakanInfo->jenis_tindakan != 8) {
                    continue; // bukan lab → skip
                }

                $jenis = 8; // pastikan masuk grup LAB
            } elseif ($jenis != 8) {
                // selain tindakan medis lab & tarif lab langsung → skip
                continue;
            }

            $dibayarPasien = $item->nominal_ditanggung_pasien ?? 0;
            $dibayarAsuransi = $item->nominal_ditanggung_asuransi ?? 0;
            $totalItem = $dibayarPasien + $dibayarAsuransi;

            // ---- MASUKKAN KE GRUP LAB ----
            $detailByJenis[8][] = [
                'uraian' => $item->deskripsi_item,
                'qty' => $item->qty,
                'harga' => $item->harga_satuan,
                'subtotal' => $item->subtotal,
                'dibayar_pasien' => $dibayarPasien,
                'dibayar_asuransi' => $dibayarAsuransi,
                'total' => $totalItem,
            ];

            $grandTotal += $totalItem;
        }

        // 5. Data ke view
        $dataUntukView = [
            'head' => $tagihanHead,
            'detailByJenis' => $detailByJenis,
            'grandTotal' => $grandTotal,
            'tipeKuitansi' => $tipeKuitansi,
            'namaKasir' => Auth::user()->nama,
            'jenis_kasir_text' => $jenis_kasir_text,
            'jenisTarifList' => $jenisTarifList,
        ];

        // 6. Generate PDF
        $pdf = PDF::loadView('reports.rincian-lab', $dataUntukView);
        $pdf->setPaper([0, 0, 612.28, 842], 'portrait');

        return $pdf->stream('rincian-lab-' . $tagihanHead->simgos_tagihan_id . '.pdf');
    }


    public function cetakResep(Request $request, $id)
    {
        // =====================================================
        // INIT & TAGIHAN
        // =====================================================
        $tagihanHead = KasirTagihanHead::findOrFail($id);
        $nopen = $tagihanHead->simgos_tagihan_id;

        // =====================================================
        // JENIS KASIR (AMAN UNTUK SEMUA RETURN)
        // =====================================================
        $jenis_kasir = $request->input('jenis_kasir');
        $jenisList = [
            1 => 'Rawat Jalan',
            2 => 'IGD',
            3 => 'Rawat Inap',
        ];
        $jenis_kasir_text = $jenisList[$jenis_kasir] ?? 'Tidak diketahui';

        // =====================================================
        // AMBIL SEMUA KUNJUNGAN BERDASARKAN NOPEN
        // =====================================================
        $kunjunganList = DB::connection('simgos_pendaftaran')->table('kunjungan')->where('NOPEN', $nopen)->orderBy('MASUK', 'desc')->get();

        if ($kunjunganList->isEmpty()) {
            return PDF::loadView('reports.resep', [
                'errorMessage' => 'Data kunjungan tidak ditemukan.',
                'head' => $tagihanHead,
                'jenis_kasir_text' => $jenis_kasir_text,
            ])->stream('resep-error-' . $nopen . '.pdf');
        }

        // =====================================================
        // AMBIL SEMUA ORDER RESEP DARI SELURUH KUNJUNGAN
        // =====================================================
        $kunjunganIds = $kunjunganList->pluck('NOMOR');

        $orderResepList = DB::connection('simgos_layanan')->table('order_resep')->whereIn('KUNJUNGAN', $kunjunganIds)->where('STATUS', 2)->orderBy('TANGGAL', 'asc')->get();

        if ($orderResepList->isEmpty()) {
            return PDF::loadView('reports.resep', [
                'errorMessage' => 'Order resep tidak ditemukan.',
                'head' => $tagihanHead,
                'jenis_kasir_text' => $jenis_kasir_text,
            ])->stream('resep-error-' . $nopen . '.pdf');
        }

        // =====================================================
        // KUMPULKAN SEMUA DETIL RESEP (UNTUK MASTER DATA)
        // =====================================================
        $excludeFarmasi = [4208, 907];
        $allFarmasi = DB::connection('simgos_layanan')->table('order_detil_resep')->whereIn('ORDER_ID', $orderResepList->pluck('NOMOR'))->where('STATUS', 1)->whereNotIn('FARMASI', $excludeFarmasi)->get();

        if ($allFarmasi->isEmpty()) {
            return PDF::loadView('reports.resep', [
                'errorMessage' => 'Detil resep kosong.',
                'head' => $tagihanHead,
                'jenis_kasir_text' => $jenis_kasir_text,
            ])->stream('resep-error-' . $nopen . '.pdf');
        }

        // =====================================================
        // MASTER DATA (DILOAD SEKALI)
        // =====================================================
        $barangList = DB::connection('simgos_inventory')
            ->table('barang')
            ->whereIn('ID', $allFarmasi->pluck('FARMASI')->unique())
            ->pluck('NAMA', 'ID');

        $frekuensiList = DB::connection('simgos_master')
            ->table('frekuensi_aturan_resep')
            ->whereIn('ID', $allFarmasi->pluck('FREKUENSI')->unique())
            ->pluck('FREKUENSI', 'ID');

        $ruteList = DB::connection('simgos_master')
            ->table('referensi')
            ->where('JENIS', 217)
            ->whereIn('ID', $allFarmasi->pluck('RUTE_PEMBERIAN')->unique())
            ->pluck('DESKRIPSI', 'ID');

        $petunjukList = DB::connection('simgos_master')
            ->table('referensi')
            ->where('JENIS', 84)
            ->whereIn('ID', $allFarmasi->pluck('PETUNJUK_RACIKAN')->unique())
            ->pluck('DESKRIPSI', 'ID');

        // =====================================================
        // BENTUK HALAMAN PDF (1 ORDER = 1 HALAMAN)
        // =====================================================
        $resepPages = [];

        foreach ($orderResepList as $orderResep) {
            $kunjungan = $kunjunganList->where('NOMOR', $orderResep->KUNJUNGAN)->first();

            if (!$kunjungan) {
                continue;
            }

            $farmasi = $allFarmasi->where('ORDER_ID', $orderResep->NOMOR)->values();

            if ($farmasi->isEmpty()) {
                continue;
            }

            // === MAP FARMASI ===
            $farmasi = $farmasi->map(function ($item) use ($barangList, $frekuensiList, $ruteList, $petunjukList) {
                $item->nama_obat = $barangList[$item->FARMASI] ?? 'Tidak ditemukan';
                $item->nama_frekuensi = $frekuensiList[$item->FREKUENSI] ?? '-';
                $item->nama_rute_pemberian = $ruteList[$item->RUTE_PEMBERIAN] ?? '-';
                $item->nama_petunjuk_racikan = $petunjukList[$item->PETUNJUK_RACIKAN] ?? null;
                return $item;
            });

            // === GROUPING RESEP ===
            $resepItems = [];
            $racikanSudahMasuk = [];

            foreach ($farmasi as $item) {
                if ((int) $item->GROUP_RACIKAN === 0) {
                    $resepItems[] = [
                        'type' => 'tunggal',
                        'data' => $item,
                    ];
                    continue;
                }

                $group = $item->GROUP_RACIKAN;

                if (!in_array($group, $racikanSudahMasuk)) {
                    $resepItems[] = [
                        'type' => 'racikan',
                        'group' => $group,
                        'items' => $farmasi->where('GROUP_RACIKAN', $group)->values(),
                    ];
                    $racikanSudahMasuk[] = $group;
                }
            }

            $resepPages[] = [
                'order' => $orderResep,
                'kunjungan' => $kunjungan,
                'farmasi' => $farmasi,
                'resepItems' => $resepItems,
            ];
        }

        if (empty($resepPages)) {
            return PDF::loadView('reports.resep', [
                'errorMessage' => 'Tidak ada resep valid untuk dicetak.',
                'head' => $tagihanHead,
                'jenis_kasir_text' => $jenis_kasir_text,
            ])->stream('resep-error-' . $nopen . '.pdf');
        }

        // =====================================================
        // CETAK PDF MULTI HALAMAN
        // =====================================================
        $pdf = PDF::loadView('reports.resep-multipage', [
            'head' => $tagihanHead,
            'nopen' => $nopen,
            'resepPages' => $resepPages,
            'jenis_kasir_text' => $jenis_kasir_text,
        ]);

        return $pdf->stream('resep-' . $nopen . '.pdf');
    }

    public function bukaSesiKasir(Request $request)
    {
        $user = Auth::user();
        $roleId = $user->role_id;

        // cek apakah role ini sudah punya sesi buka
        $sesiAktif = KasirSesi::where('status', 'BUKA')->whereHas('userPembuka', fn($q) => $q->where('role_id', $roleId))->first();
        // mapping role ke jenis kasir
        $mapJenisKasir = [
            1 => [1], // role 1 -> kasir jenis 1
            2 => [2, 3, 4, 5, 6], // role 2 -> kasir jenis 2, 3, 4, 5, dan 6 (Farmasi)
        ];

        if (!isset($mapJenisKasir[$roleId])) {
            return back()->with('error', 'Role Anda tidak memiliki akses kasir.');
        }

        $jenisList = $mapJenisKasir[$roleId];

        // Cek apakah role ini sudah punya sesi buka untuk salah satu jenis kasir
        $sesiAktif = KasirSesi::where('status', 'BUKA')->where('dibuka_oleh_user_id', $user->id)->whereIn('jenis_kasir', $jenisList)->exists();

        if ($sesiAktif) {
            return redirect()->route('dashboard')->with('info', 'Sesi kasir untuk role ini sudah dibuka.');
        }

        // Loop jenis kasir → buat beberapa sesi jika perlu
        foreach ($jenisList as $jenis) {
            KasirSesi::create([
                'nama_sesi' => 'Shift ' . strtoupper(now()->format('d-M-Y H:i')) . " (Kasir $jenis)",
                'waktu_buka' => now(),
                'dibuka_oleh_user_id' => $user->id,
                'jenis_kasir' => $jenis,
                'status' => 'BUKA',
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Sesi kasir berhasil dibuka sesuai role Anda!');
    }

    public function tutupSesiKasir(Request $request)
    {
        $user = Auth::user();
        $roleId = $user->role_id;

        // Mapping role ke jenis kasir yang harus ditutup
        $mapJenisKasir = [
            1 => [1], // role 1 -> kasir jenis 1
            2 => [2, 3, 4, 5, 6], // role 2 -> kasir jenis 2, 3, 4, 5, dan 6 (Farmasi)
        ];

        if (!isset($mapJenisKasir[$roleId])) {
            return back()->with('error', 'Role Anda tidak memiliki akses kasir.');
        }

        $jenisList = $mapJenisKasir[$roleId];

        // Ambil semua sesi aktif yang sesuai role dan jenis kasir
        $sesiAktifList = KasirSesi::where('status', 'BUKA')->whereIn('jenis_kasir', $jenisList)->get();

        if ($sesiAktifList->isEmpty()) {
            return redirect()->route('dashboard')->with('error', 'Tidak ada sesi kasir aktif untuk role Anda.');
        }

        // Tutup semua sesi kasir yang ditemukan
        foreach ($sesiAktifList as $sesi) {
            // hitung total penerimaan berdasarkan sesi ini
            $totalPenerimaan = KasirPembayaran::where('kasir_sesi_id', $sesi->id)->sum('nominal_bayar');

            $sesi->update([
                'status' => 'TUTUP',
                'waktu_tutup' => now(),
                'ditutup_oleh_user_id' => $user->id,
                'total_penerimaan_sistem' => $totalPenerimaan,
            ]);
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Semua sesi kasir untuk role Anda berhasil ditutup (' . implode(', ', $jenisList) . ').');
    }

    /**
     * Menampilkan halaman edit rincian tagihan (qty & harga satuan)
     */
    public function showEditRincian($id)
    {
        $tagihanHead = KasirTagihanHead::findOrFail($id);
        $tagihanDetail = KasirTagihanDetail::where('kasir_tagihan_head_id', $id)->get();

        return view('kasir.edit-rincian-tagihan', [
            'head' => $tagihanHead,
            'detail' => $tagihanDetail,
        ]);
    }

    /**
     * Menyimpan perubahan rincian tagihan (qty & harga satuan).
     * Setelah update, total di HEAD dihitung ulang dari semua detail.
     */
    public function updateRincianTagihan(Request $request, $id)
    {
        $tagihanHead = KasirTagihanHead::findOrFail($id);
        $jenisKasir = $request->jenis_kasir;
        $items = $request->input('items', []);

        DB::transaction(function () use ($items, $tagihanHead) {

            foreach ($items as $detailId => $data) {
                $item = KasirTagihanDetail::where('id', $detailId)
                    ->where('kasir_tagihan_head_id', $tagihanHead->id)
                    ->firstOrFail();

                $qty = (float) $data['qty'];
                $hargaSatuan = (float) $data['harga_satuan'];
                $subtotalBaru = $qty * $hargaSatuan;
                $subtotalLama = (float) $item->subtotal;

                // Jika item sudah pernah dibagi (asuransi/pasien terisi),
                // recalculate secara proporsional agar tidak kehilangan data bagi tagihan.
                $sudahDibagi = $item->nominal_ditanggung_asuransi > 0
                    || $item->nominal_ditanggung_pasien > 0;

                if ($sudahDibagi && $subtotalLama > 0) {
                    $rasio = $subtotalBaru / $subtotalLama;
                    $nominalAsuransiBaru = round($item->nominal_ditanggung_asuransi * $rasio, 2);
                    $nominalAsuransiBaru = min($nominalAsuransiBaru, $subtotalBaru); // tidak boleh melebihi subtotal baru
                    $nominalPasienBaru = round($subtotalBaru - $nominalAsuransiBaru, 2);
                } else {
                    // Belum pernah dibagi, semua dibebankan ke pasien sementara
                    $nominalAsuransiBaru = 0;
                    $nominalPasienBaru = $subtotalBaru;
                }

                $item->update([
                    'qty' => $qty,
                    'harga_satuan' => $hargaSatuan,
                    'subtotal' => $subtotalBaru,
                    'nominal_ditanggung_asuransi' => $nominalAsuransiBaru,
                    'nominal_ditanggung_pasien' => $nominalPasienBaru,
                ]);
            }

            // Hitung ulang total HEAD dari SELURUH detail (termasuk yang tidak diedit)
            $totalDariDetail = KasirTagihanDetail::where('kasir_tagihan_head_id', $tagihanHead->id)->sum('subtotal');
            $totalAsuransi = KasirTagihanDetail::where('kasir_tagihan_head_id', $tagihanHead->id)->sum('nominal_ditanggung_asuransi');
            $totalPasienKotor = KasirTagihanDetail::where('kasir_tagihan_head_id', $tagihanHead->id)->sum('nominal_ditanggung_pasien');

            $diskon = $tagihanHead->diskon_simgos ?? 0;
            $totalBayarPasienNet = max(0, $totalPasienKotor - $diskon);

            $tagihanHead->update([
                'total_asli_simgos' => $totalDariDetail,
                'total_bayar_asuransi' => $totalAsuransi,
                'total_bayar_pasien' => $totalBayarPasienNet,
            ]);
        });

        return redirect()
            ->route('kasir.tagihan.lokal', ['id' => $id, 'jenis_kasir' => $jenisKasir])
            ->with('success', 'Rincian tagihan berhasil diperbarui!');
    }
}
