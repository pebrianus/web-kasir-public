<?php

namespace App\Http\Controllers;

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
        $pasienQuery = Pasien::where('STATUS', 1);

        // 3. Jika ada pencarian, tambahkan logika 'where'
        if ($searchQuery) {
            $pasienQuery->where(function ($query) use ($searchQuery) {
                $query->where('NAMA', 'like', '%' . $searchQuery . '%')
                    ->orWhere('NORM', 'like', '%' . $searchQuery . '%');
            });
        }

        // 4. Ganti ->get() menjadi ->paginate()
        //    Kita ambil 10 data per halaman
        $pasienList = $pasienQuery->paginate(10);

        // 5. Kirim data ke view
        return view('pencarian.rawat-jalan', [
            'pasienList' => $pasienList,
            'jenis_kasir' => $jenis_kasir // Kirim juga jenis kasir ke view
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

        $processedTags = KasirTagihanHead::where('simgos_norm', $norm)
            ->select('id', 'simgos_tagihan_id', 'status_kasir')
            ->get()
            ->keyBy('simgos_tagihan_id');

        // 2. Ambil daftar tagihan (Query Kompleks - VERSI PERBAIKAN)
        $daftarTagihanQuery = DB::connection('simgos_pembayaran')->table('tagihan as t')
            ->join('pendaftaran.kunjungan as k', 't.ID', '=', 'k.NOPEN')
            ->join('pendaftaran.penjamin as pj', 't.ID', '=', 'pj.NOPEN')
            ->join('master.referensi as ref_asuransi', function ($join) {
                $join->on('pj.JENIS', '=', 'ref_asuransi.ID')
                    ->where('ref_asuransi.JENIS', 10);
            })
            ->join('master.ruangan as r', 'k.RUANGAN', '=', 'r.ID')
            ->where('r.JENIS_KUNJUNGAN', $jenis_kasir)
            ->join('master.dokter as d', 'k.DPJP', '=', 'd.ID')
            ->join('master.pegawai as p', 'd.NIP', '=', 'p.NIP')
            ->where('t.REF', $norm)
            ->where('t.STATUS', 2)
            ->select(
                't.ID as no_tagihan',
                't.TOTAL as total_tagihan',
                't.TANGGAL as tgl_tagihan',
                'r.DESKRIPSI as nama_ruangan',
                'ref_asuransi.DESKRIPSI as nama_asuransi',
                DB::raw("CONCAT_WS(' ', p.GELAR_DEPAN, p.NAMA, p.GELAR_BELAKANG) as nama_dokter")
            )
            ->orderBy('t.TANGGAL', 'desc');

        $lunasIds = $processedTags->where('status_kasir', 'lunas')->pluck('simgos_tagihan_id');
        if ($statusFilter == 'proses') {
            // Tampilkan tagihan yang BELUM lunas
            // (Termasuk yang 'draft' ATAU yang 'baru' / belum ada di lokal)
            $daftarTagihanQuery->whereNotIn('t.ID', $lunasIds);
        } else {
            // Tampilkan HANYA tagihan yang SUDAH lunas
            $daftarTagihanQuery->whereIn('t.ID', $lunasIds);
        }

        $daftarTagihan = $daftarTagihanQuery->get();

        return view('kasir.show-tagihan', [
            'pasien' => $pasien,
            'daftarTagihan' => $daftarTagihan,
            'processedTags' => $processedTags,
            'statusFilter' => $statusFilter,
            'jenis_kasir' => $jenis_kasir
        ]);
    }
    public function prosesDanBukaTagihan(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'simgos_tagihan_id' => 'required'
        ]);

        $simgosTagihanID = $request->simgos_tagihan_id;

        // 2. CEK STATUS LOKAL SEBELUMNYA
        // Coba cari tagihan head lokal berdasarkan ID SIMGOS
        $existingTagihan = KasirTagihanHead::where('simgos_tagihan_id', $simgosTagihanID)->first();

        // JIKA SUDAH ADA DAN STATUSNYA 'lunas'
        if ($existingTagihan && $existingTagihan->status_kasir == 'lunas') {
            // Langsung arahkan ke halaman rincian lokal yang sudah ada
            // Jangan lakukan snapshot ulang!
            return redirect()->route('kasir.tagihan.lokal', ['id' => $existingTagihan->id, 'jenis_kasir' => $request->jenis_kasir])
                ->with('info', 'Tagihan ini sudah lunas.');
            // Beri pesan 'info' (opsional)
        }

        // --- JIKA TIDAK ADA ATAU MASIH DRAFT: Lanjutkan proses snapshot ---

        // A. Ambil Diskon RS (Jumlahkan kolom-kolomnya)
        $diskonRSData = DB::connection('simgos_pembayaran')->table('diskon')
                        ->where('TAGIHAN', $simgosTagihanID)
                        ->first();
        
        $totalDiskonRS = 0;
        if ($diskonRSData) {
            // Jumlahkan komponen diskon (sesuaikan jika ada kolom lain di SIMGOS)
            $totalDiskonRS = ($diskonRSData->ADMINISTRASI ?? 0) + 
                             ($diskonRSData->AKOMODASI ?? 0) + 
                             ($diskonRSData->SARANA_NON_AKOMODASI ?? 0) + 
                             ($diskonRSData->PARAMEDIS ?? 0);
        }

        // B. Ambil Diskon Dokter
        $totalDiskonDokter = DB::connection('simgos_pembayaran')->table('diskon_dokter')
                        ->where('TAGIHAN', $simgosTagihanID)
                        ->sum('TOTAL');

        // C. Total Gabungan Diskon
        $totalDiskonSimgos = $totalDiskonRS + $totalDiskonDokter;

        // 3. Gunakan updateOrCreate seperti sebelumnya (aman karena sudah dicek)
        $tagihanHead = KasirTagihanHead::updateOrCreate(
            ['simgos_tagihan_id' => $simgosTagihanID], // Kunci pencarian
            [ // Data untuk di-update atau di-create
                'simgos_norm'       => $request->simgos_norm,
                'nama_pasien'       => $request->nama_pasien,
                'nama_ruangan'      => $request->nama_ruangan,
                'nama_dokter'       => $request->nama_dokter,
                'nama_asuransi'     => $request->nama_asuransi,
                'simgos_tanggal_tagihan' => $request->simgos_tanggal_tagihan,
                'total_asli_simgos' => $request->total_asli_simgos,
                'status_kasir'      => 'draft', // Selalu set ke 'draft' saat snapshot
                'diskon_simgos'        => $totalDiskonSimgos,          
                'total_bayar_pasien'   => max(0, $request->total_asli_simgos - $totalDiskonSimgos),
                'total_bayar_asuransi' => 0,
            ]
        );

        // 4. HAPUS detail lama (Wipe)
        KasirTagihanDetail::where('kasir_tagihan_head_id', $tagihanHead->id)->delete();

        // 5. Bangun "Resep Snapshot" (Replace)
        // --- Query UNION Anda yang sudah benar ada di sini ---
        $queryAdmin = DB::connection('simgos_pembayaran')->table('rincian_tagihan as rt')
            ->join('master.referensi as ref', function ($join) {
                $join->on('ref.ID', '=', 'rt.JENIS') // <-- Perbaikan: Join on ID
                    ->where('ref.JENIS', 30); // Jenis Tarif
            })
            ->where('rt.TAGIHAN', $simgosTagihanID)
            ->where('rt.JENIS', 1) // 1 = Administrasi
            ->select( // <-- PASTIKAN 5 KOLOM INI
                'rt.REF_ID as simgos_ref_id',
                'rt.JENIS as simgos_jenis_tarif',
                DB::raw("'Administrasi' as deskripsi_item"), // Ambil nama langsung
                'rt.JUMLAH as qty',
                'rt.TARIF as harga_satuan'
            );

        $queryTindakan = DB::connection('simgos_pembayaran')->table('rincian_tagihan as rt')
            ->join('layanan.tindakan_medis as tm', 'tm.ID', '=', 'rt.REF_ID')
            ->join('master.tindakan as t', 't.ID', '=', 'tm.TINDAKAN')
            ->where('rt.TAGIHAN', $simgosTagihanID)
            ->where('rt.JENIS', 3) // 3 = Tindakan
            ->where('tm.STATUS', 1) // Tindakan Medis Aktif
            ->select( // <-- PASTIKAN 5 KOLOM INI SAMA
                'rt.REF_ID as simgos_ref_id',
                'rt.JENIS as simgos_jenis_tarif',
                't.NAMA as deskripsi_item',
                'rt.JUMLAH as qty',
                'rt.TARIF as harga_satuan'
            );

        $queryFarmasi = DB::connection('simgos_pembayaran')->table('rincian_tagihan as rt')
            ->join('layanan.farmasi as f', 'f.ID', '=', 'rt.REF_ID')
            ->join('inventory.barang as b', 'b.ID', '=', 'f.FARMASI')
            ->where('rt.TAGIHAN', $simgosTagihanID)
            ->where('rt.JENIS', 4) // 4 = Farmasi
            ->whereIn('f.STATUS', [1, 2]) // Farmasi (Proses, Final)
            ->select( // <-- PASTIKAN 5 KOLOM INI SAMA
                'rt.REF_ID as simgos_ref_id',
                'rt.JENIS as simgos_jenis_tarif',
                'b.NAMA as deskripsi_item',
                'rt.JUMLAH as qty',
                'rt.TARIF as harga_satuan'
            );
        // --- Akhir Query UNION ---

        $rincianLengkap = $queryAdmin->union($queryTindakan)->union($queryFarmasi)->get();

        // 6. Masukkan data rincian baru
        $dataDetailUntukInsert = [];
        foreach ($rincianLengkap as $item) {
            $subtotal = $item->qty * $item->harga_satuan;
            $dataDetailUntukInsert[] = [
                'kasir_tagihan_head_id' => $tagihanHead->id,
                'simgos_ref_id'         => $item->simgos_ref_id,
                'simgos_jenis_tarif'    => $item->simgos_jenis_tarif,
                'deskripsi_item'        => $item->deskripsi_item,
                'qty'                   => $item->qty,
                'harga_satuan'          => $item->harga_satuan,
                'subtotal'              => $subtotal,
                'nominal_ditanggung_asuransi' => 0,
                'nominal_ditanggung_pasien'   => $subtotal,
            ];
        }
        KasirTagihanDetail::insert($dataDetailUntukInsert);

        // 7. Redirect ke halaman rincian LOKAL
        return redirect()->route('kasir.tagihan.lokal', ['id' => $tagihanHead->id, 'jenis_kasir' => $request->jenis_kasir]);
    }
    public function showLokalTagihan(Request $request,$id)
    {
        $tagihanHead = KasirTagihanHead::findOrFail($id);

        $tagihanDetail = KasirTagihanDetail::where('kasir_tagihan_head_id', $id)
            ->orderBy('simgos_jenis_tarif') // Kelompokkan per jenis
            ->get();

        $metodeBayar = AppReferensi::where('JENIS', 1) // 1 = JENIS Metode Bayar
            ->where('STATUS', true)
            ->get();

        $jenis_kasir = $request->input('jenis_kasir', 1);

        return view('kasir.rincian-lokal', [
            'head' => $tagihanHead,
            'detail' => $tagihanDetail,
            'metodeBayar' => $metodeBayar,
            'jenis_kasir' => $jenis_kasir
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
            'detail' => $tagihanDetail
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
                    'nominal_ditanggung_pasien' => $nominal_pasien
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
            'total_bayar_asuransi' => $totalAsuransiBaru
        ]);

        // Kembalikan ke halaman rincian
        return redirect()->route('kasir.tagihan.lokal', ['id' => $id, 'jenis_kasir' => $jenisKasir])
            ->with('success', 'Pembagian tagihan berhasil disimpan!');
    }
    public function storePembayaran(Request $request, $id)
    {
        // $id adalah 'kasir_tagihan_head_id'
        $request->validate([
            'metode_bayar_id' => 'required|integer',
            'nominal_bayar'   => 'required|numeric|min:0',
        ]);
        //  Cek apakah kasir aktif
        $sesiAktif = KasirSesi::where('status', 'BUKA')->first();
        if (! $sesiAktif) {
            // Tolak pembayaran dan kembalikan dengan error
            return redirect()->back()->with('error', 'Sesi kasir ditutup! Harap "Buka Kasir" terlebih dahulu untuk memproses pembayaran.');
        }
        // Ambil data tagihan lokal
        $tagihanHead = KasirTagihanHead::findOrFail($id);

        // Pastikan tagihan ini belum lunas (pencegahan double pay)
        if ($tagihanHead->status_kasir == 'lunas') {
            return redirect()->route('kasir.tagihan.lokal', ['id' => $id])
                ->with('info', 'Tagihan ini SUDAH LUNAS.');
        }
        // Cek total tagihan terkini dari SIMGOS
        $totalSimgosTerkini = DB::connection('simgos_pembayaran')->table('tagihan')
            ->where('ID', $tagihanHead->simgos_tagihan_id)
            ->value('TOTAL');

        if ((float) $tagihanHead->total_asli_simgos != (float) $totalSimgosTerkini) {
            // JIKA TIDAK SAMA, batalkan pembayaran!
            // Beritahu kasir bahwa data telah berubah dan minta mereka me-refresh.
            return redirect()->route('kasir.tagihan.lokal', ['id' => $id])
                ->with('error', 'GAGAL BAYAR: Data SIMGOS telah berubah! Total di SIMGOS (Rp ' . number_format($totalSimgosTerkini, 0, ',', '.') . ') tidak cocok dengan data snapshot Anda (Rp ' . number_format($tagihanHead->total_asli_simgos, 0, ',', '.') . '). Silakan klik tombol Refresh (R) di sidebar kanan untuk mengambil data terbaru.');
        }

        // Hitung nominal yang SEHARUSNYA dibayar (Server Side Calculation)
        // Rumus: (Total Asli - Diskon - Ditanggung Asuransi)
        $nominalWajibBayar = $tagihanHead->total_asli_simgos - $tagihanHead->diskon_simgos - $tagihanHead->total_bayar_asuransi;
        
        // Opsional: Jika Anda ingin membolehkan pembayaran parsial (mencicil), 
        // gunakan $request->nominal_bayar. 
        // Tapi jika harus lunas sekaligus, gunakan $nominalWajibBayar.
        // Di sini saya asumsikan harus sesuai tagihan (untuk keamanan):
        $nominalFinal = ($nominalWajibBayar < 0) ? 0 : $nominalWajibBayar;

        // 1. Simpan catatan transaksi di tabel log
        KasirPembayaran::create([
            'kasir_tagihan_head_id' => $tagihanHead->id,
            'user_id'               => Auth::id(), // ID kasir yang sedang login
            'metode_bayar_id'       => $request->metode_bayar_id,
            'nominal_bayar'         => $request->nominal_bayar,
            'kasir_sesi_id'         => $sesiAktif->id
        ]);

        // 2. Update status tagihan utama menjadi 'lunas'
        $tagihanHead->update([
            'status_kasir' => 'lunas'
        ]);

        // 3. Kembalikan ke halaman rincian
        return redirect()->route('kasir.tagihan.lokal', ['id' => $id])
            ->with('success', 'Pembayaran berhasil disimpan!');
    }
    /**
     * Mencetak Kuitansi Pasien atau Asuransi dalam format PDF.
     * VERSI DENGAN AGREGRASI DETAIL TINDAKAN.
     */
    public function cetakKuitansi($id)
    {
        // 1. Ambil data header tagihan
        $tagihanHead = KasirTagihanHead::findOrFail($id);

        // 2. Tentukan Tipe Kuitansi
        $routeName = Route::currentRouteName();
        $tipeKuitansi = ($routeName == 'kuitansi.cetak.pasien') ? 'Pasien' : 'Asuransi';

        // 3. Ambil data detail tagihan LOKAL kita
        $tagihanDetail = KasirTagihanDetail::where('kasir_tagihan_head_id', $id)->get();

        // 4. Lakukan Agregasi (Rekap) - Logika Baru
        $rekapData = [];
        $grandTotal = 0;

        // Siapkan array untuk menampung subtotal per kategori
        $subtotals = [
            'Administrasi' => 0,
            'Pemeriksaan Dokter' => 0, // Dulu Konsultasi (JENIS=3)
            'Pemeriksaan Radiologi' => 0, // JENIS=7
            'Pemeriksaan Lab' => 0, // JENIS=8
            'Tindakan Dokter' => 0, // Default untuk JENIS=3 lainnya
            'Farmasi' => 0,
            // Tindakan Keperawatan akan ditangani terpisah
        ];
        $tindakanKeperawatan = []; // Array untuk item keperawatan

        foreach ($tagihanDetail as $item) {
            $nominal = ($tipeKuitansi == 'Pasien') ? $item->nominal_ditanggung_pasien : $item->nominal_ditanggung_asuransi;

            // Hanya proses jika nominal > 0
            if ($nominal <= 0) {
                continue;
            }

            // --- Logika Pengelompokan ---
            switch ($item->simgos_jenis_tarif) {
                case 1: // Administrasi
                    $subtotals['Administrasi'] += $nominal;
                    break;

                case 4: // Farmasi
                    $subtotals['Biaya Obat'] += $nominal;
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
                            case 5: // Keperawatan - TIDAK DIGABUNG
                                // Simpan sebagai item terpisah
                                $tindakanKeperawatan[] = [
                                    'uraian' => $tindakanInfo->nama_tindakan, // Ambil nama spesifik
                                    'subtotal' => $nominal
                                ];
                                break;
                            default: // Jenis tindakan lain (1, 2, 4, 6, 10, dll)
                                $subtotals['Tindakan Dokter'] += $nominal;
                                break;
                        }
                    } else {
                        // Jika info tindakan tidak ditemukan (jarang terjadi), masukkan ke default
                        $subtotals['Tindakan Dokter'] += $nominal;
                    }
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
        // Tambahkan item keperawatan (jika ada)
        $rekapData = array_merge($rekapData, $tindakanKeperawatan);

        // 6. Siapkan data untuk dikirim ke View
        $dataUntukView = [
            'head' => $tagihanHead,
            'rekap' => $rekapData,
            'grandTotal' => $grandTotal,
            'tipeKuitansi' => $tipeKuitansi,
            'namaKasir' => Auth::user()->nama
        ];

        // 7. Load View PDF dan kirim data
        $pdf = PDF::loadView('reports.kuitansi', $dataUntukView);
        $pdf->setPaper([0, 0, 612.28, 396.85], 'portrait');


        // 8. Tampilkan PDF
        return $pdf->stream('kuitansi-' . $tagihanHead->simgos_tagihan_id . '.pdf');
    }
    /**
     * Menyegarkan (refresh) data rincian tagihan dari SIMGOS.
     * Hanya berjalan jika status kasir masih 'draft'.
     */
    public function refreshTagihanSimgos(Request $request, $id) // $id adalah kasir_tagihan_head_id
    {
        // 1. Cari Tagihan Head Lokal
        $tagihanHead = KasirTagihanHead::findOrFail($id);

        // 2. PERIKSA STATUS: HANYA JALANKAN JIKA 'draft'
        if ($tagihanHead->status_kasir != 'draft') {
            return redirect()->route('kasir.tagihan.lokal', ['id' => $id])
                ->with('error', 'Tagihan ini sudah lunas, data tidak bisa di-refresh.');
        }

        // --- Lanjutkan proses refresh (mirip prosesDanBukaTagihan) ---
        $simgosTagihanID = $tagihanHead->simgos_tagihan_id; // Ambil ID SIMGOS dari head

        // 3. HAPUS detail lama (Wipe)
        KasirTagihanDetail::where('kasir_tagihan_head_id', $tagihanHead->id)->delete();

        // 4. Bangun "Resep Snapshot" (Replace)
        // --- Query UNION Anda ---
        $queryAdmin = DB::connection('simgos_pembayaran')->table('rincian_tagihan as rt')
            ->join('master.referensi as ref', function ($join) {
                $join->on('ref.ID', '=', 'rt.JENIS')->where('ref.JENIS', 30);
            })->where('rt.TAGIHAN', $simgosTagihanID)->where('rt.JENIS', 1)
            ->select('rt.REF_ID as simgos_ref_id', 'rt.JENIS as simgos_jenis_tarif', DB::raw("'Administrasi' as deskripsi_item"), 'rt.JUMLAH as qty', 'rt.TARIF as harga_satuan');

        $queryTindakan = DB::connection('simgos_pembayaran')->table('rincian_tagihan as rt')
            ->join('layanan.tindakan_medis as tm', 'tm.ID', '=', 'rt.REF_ID')
            ->join('master.tindakan as t', 't.ID', '=', 'tm.TINDAKAN')
            ->where('rt.TAGIHAN', $simgosTagihanID)->where('rt.JENIS', 3)->where('tm.STATUS', 1)
            ->select('rt.REF_ID as simgos_ref_id', 'rt.JENIS as simgos_jenis_tarif', 't.NAMA as deskripsi_item', 'rt.JUMLAH as qty', 'rt.TARIF as harga_satuan');

        $queryFarmasi = DB::connection('simgos_pembayaran')->table('rincian_tagihan as rt')
            ->join('layanan.farmasi as f', 'f.ID', '=', 'rt.REF_ID')
            ->join('inventory.barang as b', 'b.ID', '=', 'f.FARMASI')
            ->where('rt.TAGIHAN', $simgosTagihanID)->where('rt.JENIS', 4)->whereIn('f.STATUS', [1, 2])
            ->select('rt.REF_ID as simgos_ref_id', 'rt.JENIS as simgos_jenis_tarif', 'b.NAMA as deskripsi_item', 'rt.JUMLAH as qty', 'rt.TARIF as harga_satuan');
        // --- Akhir Query UNION ---

        $rincianLengkap = $queryAdmin->union($queryTindakan)->union($queryFarmasi)->get();

        // 5. Masukkan data rincian baru
        $dataDetailUntukInsert = [];
        $totalAsliBaru = 0; // Hitung ulang total asli
        foreach ($rincianLengkap as $item) {
            $subtotal = $item->qty * $item->harga_satuan;
            $dataDetailUntukInsert[] = [
                'kasir_tagihan_head_id' => $tagihanHead->id,
                'simgos_ref_id'         => $item->simgos_ref_id,
                'simgos_jenis_tarif'    => $item->simgos_jenis_tarif,
                'deskripsi_item'        => $item->deskripsi_item,
                'qty'                   => $item->qty,
                'harga_satuan'          => $item->harga_satuan,
                'subtotal'              => $subtotal,
                'nominal_ditanggung_asuransi' => 0, // Reset pembagian
                'nominal_ditanggung_pasien'   => $subtotal, // Reset pembagian
            ];
            $totalAsliBaru += $subtotal; // Akumulasi total baru
        }
        KasirTagihanDetail::insert($dataDetailUntukInsert);

        // 6. Update total asli di header
        $tagihanHead->update(['total_asli_simgos' => $totalAsliBaru]);

        // 7. Redirect kembali ke halaman rincian dengan pesan sukses
        return redirect()->route('kasir.tagihan.lokal', ['id' => $id])
            ->with('success', 'Data tagihan berhasil di-refresh dari SIMGOS.');
    }
    public function bukaSesiKasir(Request $request)
    {
        $user = Auth::user();
        $roleId = $user->role_id;

        // cek apakah role ini sudah punya sesi buka
        $sesiAktif = KasirSesi::where('status', 'BUKA')
            ->whereHas('userPembuka', fn($q) => $q->where('role_id', $roleId))
            ->first();

        if ($sesiAktif) {
            return redirect()->route('dashboard')->with('info', 'Sesi kasir untuk role ini sudah dibuka.');
        }

        KasirSesi::create([
            'nama_sesi' => 'Shift ' . now()->format('d-m-Y H:i'),
            'waktu_buka' => now(),
            'dibuka_oleh_user_id' => $user->id,
            'status' => 'BUKA'
        ]);

        return redirect()->route('dashboard')->with('success', 'Sesi kasir berhasil dibuka untuk role Anda!');
    }

    public function tutupSesiKasir(Request $request)
    {
        $user = Auth::user();
        $roleId = $user->role_id;

        $sesiAktif = KasirSesi::where('status', 'BUKA')
            ->whereHas('userPembuka', fn($q) => $q->where('role_id', $roleId))
            ->first();

        if (! $sesiAktif) {
            return redirect()->route('dashboard')->with('error', 'Tidak ada sesi kasir aktif untuk role Anda.');
        }

        $totalPenerimaan = KasirPembayaran::where('kasir_sesi_id', $sesiAktif->id)->sum('nominal_bayar');

        $sesiAktif->update([
            'status' => 'TUTUP',
            'waktu_tutup' => now(),
            'ditutup_oleh_user_id' => $user->id,
            'total_penerimaan_sistem' => $totalPenerimaan
        ]);

        return redirect()->route('dashboard')->with('success', 'Sesi kasir untuk role Anda berhasil ditutup!');
    }
}
