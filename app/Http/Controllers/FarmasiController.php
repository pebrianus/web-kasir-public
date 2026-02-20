<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FarmasiController extends Controller
{


    public function tagihanFarmasi(Request $request)
    {
        $data = DB::connection('simgos_penjualan')
            ->table('penjualan as p')
            ->select(
                'p.NOMOR',
                'p.PENGUNJUNG',
                'p.TANGGAL',

                'd.BARANG',
                'b.NAMA as NAMA_BARANG',

                'd.HARGA_BARANG',
                'hb.HARGA_JUAL as HARGA_JUAL_BARANG',

                'd.JUMLAH',

                'r.DESKRIPSI as ATURAN_PAKAI_TEXT'
            )
            ->join('penjualan_detil as d', 'p.NOMOR', '=', 'd.PENJUALAN_ID')

            // JOIN barang
            ->leftJoin(
                DB::raw('inventory.barang as b'),
                'd.BARANG',
                '=',
                'b.ID'
            )

            // JOIN harga_barang
            ->leftJoin(
                DB::raw('inventory.harga_barang as hb'),
                'd.HARGA_BARANG',
                '=',
                'hb.ID'
            )

            // JOIN referensi
            ->leftJoin(
                DB::raw('master.referensi as r'),
                function ($join) {
                    $join->on('d.ATURAN_PAKAI', '=', 'r.ID')
                        ->where('r.JENIS', '=', 41);
                }
            )

            ->where('p.STATUS', 2)
            ->orderBy('p.TANGGAL', 'desc')
            ->paginate(10);

        dd($data->toArray());

        return view('farmasi.index', compact('data'));
    }

    public function showTagihanFarmasi($id)
    {

        return view('farmasi.show');
    }




}
