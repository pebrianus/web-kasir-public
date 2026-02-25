<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\KasirPenjualanHead;
use App\Models\KasirPenjualanDetail;
use App\Models\KasirPembayaran;
use App\Models\KasirSesi;
use Illuminate\Support\Facades\Auth;

class FarmasiController extends Controller
{
    public function tagihanFarmasi(Request $request)
    {
        // Default filter ke 'proses' (Belum Selesai)
        $statusFilter = $request->input('status', 'proses');

        if ($statusFilter == 'proses') {
            // ============================================
            // 1A. TAB "BELUM SELESAI" (TARIK DARI SIMGOS)
            // ============================================

            // Ambil ID Penjualan yang sudah lunas di lokal agar tidak dobel muncul
            $lunasIds = KasirPenjualanHead::where('status_kasir', 'lunas')->pluck('simgos_penjualan_id')->toArray();

            // Paginator dari tabel Penjualan SIMGOS
            $paginator = DB::connection('simgos_penjualan')->table('penjualan as p')->select('p.NOMOR', 'p.PENGUNJUNG', 'p.TANGGAL')->where('p.STATUS', 2)->whereNotIn('p.NOMOR', $lunasIds)->orderBy('p.TANGGAL', 'desc')->paginate(10);

            // Ambil nomor list di halaman ini untuk mencari total di tabel tagihan
            $nomorList = $paginator->pluck('NOMOR')->toArray();

            // Ambil TOTAL harganya langsung dari tabel tagihan (SIMGOS)
            $tagihanTotal = DB::connection('simgos_pembayaran')->table('tagihan')->whereIn('ID', $nomorList)->where('REF', 0)->pluck('TOTAL', 'ID');

            // Format datanya agar mirip dengan kolom database lokal (Normalisasi)
            $data = $paginator->getCollection()->map(function ($item) use ($tagihanTotal) {
                return (object) [
                    'simgos_penjualan_id' => $item->NOMOR,
                    'nama_pengunjung' => $item->PENGUNJUNG,
                    'simgos_tanggal' => $item->TANGGAL,
                    'total_tagihan' => isset($tagihanTotal[$item->NOMOR]) ? $tagihanTotal[$item->NOMOR] : 0,
                ];
            });

            // Set ulang collection ke paginator
            $paginator->setCollection($data);
            $dataList = $paginator;
        } else {
            // ============================================
            // 1B. TAB "SELESAI" (TARIK DARI LOKAL)
            // ============================================
            $dataList = KasirPenjualanHead::where('status_kasir', 'lunas')->orderBy('simgos_tanggal', 'desc')->paginate(10);
        }

        return view('farmasi.index', [
            'data' => $dataList,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function showTagihanFarmasi($id)
    {
        // 1. CEK DATA DI LOKAL DULU
        $localHead = KasirPenjualanHead::with('details')->where('simgos_penjualan_id', $id)->first();

        // JIKA SUDAH LUNAS, Buka data lokal tanpa snapshot ulang
        if ($localHead && $localHead->status_kasir == 'lunas') {
            return view('farmasi.show', ['tagihan' => $localHead]);
        }

        // ============================================
        // 2. PROSES SNAPSHOT (COPY DARI SIMGOS KE LOKAL)
        // ============================================

        // A. Tarik Header SIMGOS
        $headerSimgos = DB::connection('simgos_penjualan')->table('penjualan as p')->select('p.NOMOR', 'p.PENGUNJUNG', 'p.JENIS', 'p.DOKTER', 'p.KETERANGAN', 'p.TANGGAL')->where('p.STATUS', 2)->where('p.NOMOR', $id)->first();

        if (!$headerSimgos) {
            abort(404, 'Tagihan Penjualan tidak ditemukan di SIMGOS');
        }

        // B. Tarik Total Tagihan
        $totalSimgos = DB::connection('simgos_pembayaran')->table('tagihan')->where('ID', $id)->where('REF', 0)->value('TOTAL') ?? 0;

        // C. Update/Create Header LOKAL
        $head = KasirPenjualanHead::updateOrCreate(
            ['simgos_penjualan_id' => $id],
            [
                'nama_pengunjung' => $headerSimgos->PENGUNJUNG,
                'jenis_penjualan' => $headerSimgos->JENIS,
                'nama_dokter' => $headerSimgos->DOKTER,
                'keterangan' => $headerSimgos->KETERANGAN,
                'simgos_tanggal' => $headerSimgos->TANGGAL,
                'total_tagihan' => $totalSimgos,
                'status_kasir' => 'draft', // Selalu draft saat snapshot
            ],
        );

        // D. Hapus Detail Lama (Wipe)
        KasirPenjualanDetail::where('kasir_penjualan_head_id', $head->id)->delete();

        // E. Tarik Detail SIMGOS
        $detailsSimgos = DB::connection('simgos_penjualan')->table('penjualan_detil as d')->select('d.BARANG', 'b.NAMA as NAMA_BARANG', 'd.JUMLAH', 'hb.HARGA_JUAL')->leftJoin(DB::raw('inventory.barang as b'), 'd.BARANG', '=', 'b.ID')->leftJoin(DB::raw('inventory.harga_barang as hb'), 'd.HARGA_BARANG', '=', 'hb.ID')->where('d.PENJUALAN_ID', $id)->get();

        // F. Insert Detail Baru ke LOKAL
        $insertDetails = [];
        foreach ($detailsSimgos as $d) {
            $qty = (float) $d->JUMLAH;
            $harga = (float) $d->HARGA_JUAL;
            $sub = $qty * $harga;

            $insertDetails[] = [
                'kasir_penjualan_head_id' => $head->id,
                'simgos_barang_id' => $d->BARANG,
                'nama_barang' => $d->NAMA_BARANG,
                'qty' => $qty,
                'harga_satuan' => $harga,
                'subtotal' => $sub,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        KasirPenjualanDetail::insert($insertDetails);

        // 3. Eager Load (Muat Ulang) relasi details untuk dikirim ke view
        $head->load('details');

        return view('farmasi.show', ['tagihan' => $head]);
    }

    public function prosesPembayaran(Request $request, $id)
    {
        // 1. Cari data head lokal berdasarkan ID penjualan SIMGOS
        $head = KasirPenjualanHead::where('simgos_penjualan_id', $id)->firstOrFail();

        // 2. Cegah double-submit jika sudah lunas
        if ($head->status_kasir == 'lunas') {
            return redirect()->back()->with('info', 'Tagihan ini sudah lunas.');
        }

        // 3. CEK SESI KASIR AKTIF
        // Kita cari sesi kasir yang sedang 'BUKA' dan dimiliki oleh user yang sedang login
        $sesiAktif = KasirSesi::where('status', 'BUKA')
            ->where('dibuka_oleh_user_id', Auth::id())
            ->where('jenis_kasir', 6)
            ->first();
        if (!$sesiAktif) {
            return redirect()->back()->with('error', 'Sesi kasir belum dibuka! Silakan buka sesi kasir terlebih dahulu.');
        }

        // 4. CATAT KE TABEL PEMBAYARAN PUSAT
        KasirPembayaran::create([
            'kasir_tagihan_head_id' => null, // Sengaja dikosongkan karena ini bukan rawat jalan
            'kasir_penjualan_head_id' => $head->id, // Isi dengan ID farmasi
            'user_id' => Auth::id(), // ID Kasir
            'metode_bayar_id' => 1, // Asumsi 1 = Tunai (Nanti kita bisa tambahkan dropdown metode bayar jika butuh)
            'nominal_bayar' => $head->total_tagihan,
            'kasir_sesi_id' => $sesiAktif->id, // Kaitkan dengan shift kasir
        ]);

        // 5. Update status jadi Lunas di database lokal kita
        $head->update([
            'status_kasir' => 'lunas',
        ]);

        // 6. Redirect kembali dengan pesan sukses
        return redirect()->route('farmasi.show', $id)->with('success', 'Pembayaran berhasil diproses dan masuk ke rekap shift kasir!');
    }

    public function cetakKuitansiFarmasi($id)
    {
        $connection = DB::connection('simgos_penjualan');

        // ==============================
        // 1️⃣ AMBIL HEADER
        // ==============================
        $header = $connection->table('penjualan as p')->select('p.NOMOR', 'p.PENGUNJUNG', 'p.TANGGAL', 'p.DOKTER')->where('p.STATUS', 2)->where('p.NOMOR', $id)->first();

        if (!$header) {
            abort(404, 'Tagihan tidak ditemukan');
        }

        // ==============================
        // 2️⃣ AMBIL DETAIL
        // ==============================
        $details = $connection->table('penjualan_detil as d')->select('d.PENJUALAN_ID', 'b.NAMA as NAMA_BARANG', 'd.JUMLAH', 'hb.HARGA_JUAL as HARGA_JUAL_BARANG')->leftJoin(DB::raw('inventory.barang as b'), 'd.BARANG', '=', 'b.ID')->leftJoin(DB::raw('inventory.harga_barang as hb'), 'd.HARGA_BARANG', '=', 'hb.ID')->where('d.PENJUALAN_ID', $id)->get();

        // ==============================
        // 3️⃣ HITUNG TOTAL
        // ==============================
        $total = 0;

        foreach ($details as $item) {
            $qty = (float) $item->JUMLAH;
            $harga = (float) $item->HARGA_JUAL_BARANG;
            $total += $qty * $harga;
        }

        // ==============================
        // 4️⃣ FORMAT DATA UNTUK VIEW
        // ==============================
        $dataUntukView = [
            'tagihan' => (object) [
                'NOMOR' => $header->NOMOR,
                'PENGUNJUNG' => $header->PENGUNJUNG,
                'TANGGAL' => $header->TANGGAL,
                'DOKTER' => $header->DOKTER ?: '-',
                'OBAT' => $details,
                'TOTAL' => $total,
            ],
        ];

        // ==============================
        // 5️⃣ LOAD PDF
        // ==============================
        $pdf = Pdf::loadView('reports.kuitansi-farmasi', $dataUntukView);

        // Ukuran kertas (contoh landscape kecil seperti contoh kamu)
        $pdf->setPaper([0, 0, 612.28, 396.85], 'portrait');

        return $pdf->stream('kuitansi-farmasi-' . $header->NOMOR . '.pdf');
    }

    public function batalPembayaranFarmasi(Request $request, $id)
    {
        // $id adalah simgos_penjualan_id (string)
        $penjualanHead = KasirPenjualanHead::where('simgos_penjualan_id', $id)
            ->where('status_kasir', 'lunas')
            ->first();

        if (!$penjualanHead) {
            return redirect()
                ->route('farmasi.show', $id)
                ->with('error', 'Transaksi tidak ditemukan atau statusnya belum lunas.');
        }

        DB::transaction(function () use ($penjualanHead) {

            // Soft delete pembayaran
            KasirPembayaran::where('kasir_penjualan_head_id', $penjualanHead->id)
                ->delete();

            // Kembalikan status jadi draft
            $penjualanHead->update([
                'status_kasir' => 'draft',
            ]);
        });

        return redirect()
            ->route('farmasi.show', $id)
            ->with('success', 'Pembayaran berhasil dibatalkan. Status transaksi kembali menjadi DRAFT dan dikeluarkan dari laporan shift kasir.');
    }
}


