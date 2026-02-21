<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class FarmasiController extends Controller
{


    public function tagihanFarmasi(Request $request)
    {
        $connection = DB::connection('simgos_penjualan');

        // ==============================
        // 1️⃣ AMBIL HEADER (PAGINATE)
        // ==============================
        $headers = $connection->table('penjualan as p')
            ->select(
                'p.NOMOR',
                'p.PENGUNJUNG',
                'p.TANGGAL'
            )
            ->where('p.STATUS', 2)
            ->orderBy('p.TANGGAL', 'desc')
            ->paginate(10);

        // Ambil semua NOMOR yang ada di halaman ini
        $nomorList = $headers->pluck('NOMOR')->toArray();

        // ==============================
        // 2️⃣ AMBIL DETAIL BERDASARKAN NOMOR
        // ==============================
        $details = $connection->table('penjualan_detil as d')
            ->select(
                'd.PENJUALAN_ID',
                'd.BARANG',
                'b.NAMA as NAMA_BARANG',
                'd.JUMLAH',
                'hb.HARGA_JUAL as HARGA_JUAL_BARANG',
                'r.DESKRIPSI as ATURAN_PAKAI_TEXT'
            )
            ->leftJoin(DB::raw('inventory.barang as b'), 'd.BARANG', '=', 'b.ID')
            ->leftJoin(DB::raw('inventory.harga_barang as hb'), 'd.HARGA_BARANG', '=', 'hb.ID')
            ->leftJoin(DB::raw('master.referensi as r'), function ($join) {
                $join->on('d.ATURAN_PAKAI', '=', 'r.ID')
                    ->where('r.JENIS', '=', 41);
            })
            ->whereIn('d.PENJUALAN_ID', $nomorList)
            ->get()
            ->groupBy('PENJUALAN_ID');

        // ==============================
        // 3️⃣ GABUNGKAN HEADER + DETAIL
        // ==============================
        $data = $headers->getCollection()->map(function ($header) use ($details) {

            $obat = isset($details[$header->NOMOR])
                ? $details[$header->NOMOR]
                : collect();

            return (object) [
                'NOMOR' => $header->NOMOR,
                'PENGUNJUNG' => $header->PENGUNJUNG,
                'TANGGAL' => $header->TANGGAL,
                'OBAT' => $obat
            ];
        });

        // Replace collection paginator dengan data yang sudah digabung
        $headers->setCollection($data);


        return view('farmasi.index', [
            'data' => $headers
        ]);
    }

    public function showTagihanFarmasi($id)
    {
        $connection = DB::connection('simgos_penjualan');

        // ==============================
        // 1️⃣ AMBIL HEADER BERDASARKAN ID
        // ==============================
        $header = $connection->table('penjualan as p')
            ->select(
                'p.NOMOR',
                'p.PENGUNJUNG',
                'p.TANGGAL',
                'p.DOKTER'
            )
            ->where('p.STATUS', 2)
            ->where('p.NOMOR', $id)
            ->first();

        // Jika tidak ditemukan → 404
        if (!$header) {
            abort(404, 'Tagihan tidak ditemukan');
        }

        // ==============================
        // 2️⃣ AMBIL DETAIL BERDASARKAN ID
        // ==============================
        $details = $connection->table('penjualan_detil as d')
            ->select(
                'd.PENJUALAN_ID',
                'd.BARANG',
                'b.NAMA as NAMA_BARANG',
                'd.JUMLAH',
                'hb.HARGA_JUAL as HARGA_JUAL_BARANG',
                'r.DESKRIPSI as ATURAN_PAKAI_TEXT'
            )
            ->leftJoin(DB::raw('inventory.barang as b'), 'd.BARANG', '=', 'b.ID')
            ->leftJoin(DB::raw('inventory.harga_barang as hb'), 'd.HARGA_BARANG', '=', 'hb.ID')
            ->leftJoin(DB::raw('master.referensi as r'), function ($join) {
                $join->on('d.ATURAN_PAKAI', '=', 'r.ID')
                    ->where('r.JENIS', '=', 41);
            })
            ->where('d.PENJUALAN_ID', $id)
            ->get();

        // ==============================
        // 3️⃣ GABUNGKAN DATA
        // ==============================
        $tagihan = (object) [
            'NOMOR' => $header->NOMOR,
            'PENGUNJUNG' => $header->PENGUNJUNG,
            'TANGGAL' => $header->TANGGAL,
            'DOKTER' => $header->DOKTER,
            'OBAT' => $details
        ];

        return view('farmasi.show', compact('tagihan'));
    }



    public function cetakKuitansiFarmasi($id)
    {
        $connection = DB::connection('simgos_penjualan');

        // ==============================
        // 1️⃣ AMBIL HEADER
        // ==============================
        $header = $connection->table('penjualan as p')
            ->select(
                'p.NOMOR',
                'p.PENGUNJUNG',
                'p.TANGGAL',
                'p.DOKTER'
            )
            ->where('p.STATUS', 2)
            ->where('p.NOMOR', $id)
            ->first();

        if (!$header) {
            abort(404, 'Tagihan tidak ditemukan');
        }

        // ==============================
        // 2️⃣ AMBIL DETAIL
        // ==============================
        $details = $connection->table('penjualan_detil as d')
            ->select(
                'd.PENJUALAN_ID',
                'b.NAMA as NAMA_BARANG',
                'd.JUMLAH',
                'hb.HARGA_JUAL as HARGA_JUAL_BARANG'
            )
            ->leftJoin(DB::raw('inventory.barang as b'), 'd.BARANG', '=', 'b.ID')
            ->leftJoin(DB::raw('inventory.harga_barang as hb'), 'd.HARGA_BARANG', '=', 'hb.ID')
            ->where('d.PENJUALAN_ID', $id)
            ->get();

        // ==============================
        // 3️⃣ HITUNG TOTAL
        // ==============================
        $total = 0;

        foreach ($details as $item) {
            $qty = (float) $item->JUMLAH;
            $harga = (float) $item->HARGA_JUAL_BARANG;
            $total += ($qty * $harga);
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
                'TOTAL' => $total
            ]
        ];

        // ==============================
        // 5️⃣ LOAD PDF
        // ==============================
        $pdf = Pdf::loadView('reports.kuitansi-farmasi', $dataUntukView);

        // Ukuran kertas (contoh landscape kecil seperti contoh kamu)
        $pdf->setPaper([0, 0, 612.28, 396.85], 'portrait');

        return $pdf->stream('kuitansi-farmasi-' . $header->NOMOR . '.pdf');
    }




}
