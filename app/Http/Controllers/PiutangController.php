<?php

namespace App\Http\Controllers;

use App\Models\KasirTagihanPiutang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;



class PiutangController extends Controller
{
    public function indexPiutang(Request $request)
    {
        $statusFilter = $request->query('status', 'belum');
        $search = $request->query('search');
        $query = KasirTagihanPiutang::query();
        $roleId = auth()->user()->role_id;

        $jenisKasir = $roleId == 1 ? [1] : [2, 3, 4, 5];

        $allowedTagihanIds = DB::connection('simgos_pembayaran')
            ->table('tagihan as t')
            ->join('tagihan_pendaftaran as tp', 'tp.TAGIHAN', '=', 't.ID')
            ->join('pendaftaran.kunjungan as k', 'tp.PENDAFTARAN', '=', 'k.NOPEN')
            ->join('master.ruangan as r', 'k.RUANGAN', '=', 'r.ID')
            ->where('r.JENIS', 5)
            ->whereNull('k.REF')
            ->where('t.STATUS', 2)
            ->where('tp.STATUS', 1)
            ->where('tp.UTAMA', 1)
            ->pluck('t.ID');

        $query->whereIn('simgos_tagihan_id', $allowedTagihanIds);

        switch ($statusFilter) {
            case 'lunas':
                $query->lunas();
                break;
            case 'outstanding':
                $query->outstanding();
                break;
            case 'sebagian':
                $query->sebagian();
                break;
            default:
                $query->belumLunas();
                break;
        }

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_pasien', 'like', "%{$search}%")
                    ->orWhere('simgos_norm', 'like', "%{$search}%");
            });
        }

        $data = $query
            ->select([
                'id',
                'simgos_norm            as no_rm',
                'nama_pasien            as nama',
                'nama_asuransi',
                'total_tagihan_asuransi as total_biaya',
                'nominal_piutang        as piutang',
                'created_at             as tanggal',
                'status',
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // TEMPORARY: duplikat data untuk testing pagination
        $items = $data->items();
        $duplicated = collect(array_merge(...array_fill(0, 30, $items)));

        // Override $data dengan LengthAwarePaginator baru
        $data = new \Illuminate\Pagination\LengthAwarePaginator(
            $duplicated->forPage(\Illuminate\Pagination\Paginator::resolveCurrentPage(), 20),
            $duplicated->count(),
            20,
            \Illuminate\Pagination\Paginator::resolveCurrentPage(),
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('piutang.index', compact('data', 'statusFilter', 'search'));
    }

    // Detail piutang
    public function detailPiutang($id)
    {
        $piutang = KasirTagihanPiutang::with(['tagihanHead.details', 'user'])
            ->findOrFail($id);

        $tagihanHead = $piutang->tagihanHead;
        $tagihanDetail = $tagihanHead ? $tagihanHead->details : collect();

        return view('piutang.detail', compact('piutang', 'tagihanHead', 'tagihanDetail'));
    }

    // Proses pembayaran (sebagian / lunas penuh)
    public function bayarPiutang(Request $request, $id)
    {
        $piutang = KasirTagihanPiutang::with('tagihanHead')->findOrFail($id);

        $request->validate([
            'nominal_bayar' => 'required|numeric|min:1|max:' . $piutang->nominal_sisa,
            'tanggal_bayar' => 'required|date',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $piutang->tambahPembayaran((float) $request->nominal_bayar);

        if ($request->keterangan) {
            $piutang->keterangan = $request->keterangan;
            $piutang->save();
        }

        // Jika piutang sudah lunas, update status_kasir di tagihan head
        if ($piutang->status === 'lunas' && $piutang->tagihanHead) {
            $piutang->tagihanHead->update([
                'status_kasir' => 'lunas',
            ]);
        }

        $msg = $piutang->status === 'lunas'
            ? 'Piutang berhasil dilunasi.'
            : 'Pembayaran sebagian berhasil disimpan.';

        return redirect()->route('piutang.detail', $id)->with('success', $msg);
    }

    // Write-off / hapus buku
    public function hapusBukuPiutang($id)
    {
        $piutang = KasirTagihanPiutang::findOrFail($id);

        $keteranganLama = $piutang->keterangan ? $piutang->keterangan . ' | ' : '';

        $piutang->update([
            'status' => 'lunas',
            'nominal_sisa' => 0,
            'tanggal_lunas' => now()->toDateString(),
            'keterangan' => $keteranganLama . 'Write-off',
        ]);

        return redirect()->route('piutang.detail', $id)->with('info', 'Piutang telah dihapusbukukan.');
    }

    // Batalkan pelunasan piutang
// Batalkan pelunasan piutang
    public function batalLunasPiutang(Request $request, $id)
    {
        $piutang = KasirTagihanPiutang::with('tagihanHead')->findOrFail($id);

        // Pastikan status memang lunas sebelum dibatalkan
        if ($piutang->status !== 'lunas') {
            return redirect()->route('piutang.detail', $id)
                ->with('error', 'Piutang ini belum berstatus lunas, tidak dapat dibatalkan.');
        }

        $keteranganLama = $piutang->keterangan ? $piutang->keterangan . ' | ' : '';
        $keteranganBaru = $keteranganLama
            . 'Pelunasan dibatalkan oleh ' . auth()->user()->nama
            . ' pada ' . now()->format('d/m/Y H:i');

        $piutang->update([
            'status' => 'outstanding',
            'nominal_terbayar' => 0,
            'nominal_sisa' => $piutang->nominal_piutang,
            'tanggal_lunas' => null,
            'keterangan' => $keteranganBaru,
        ]);

        // Sinkronkan status_kasir di tagihan head
        if ($piutang->tagihanHead) {
            $piutang->tagihanHead->update([
                'status_kasir' => 'outstanding',
            ]);
        }

        return redirect()->route('piutang.detail', $id)
            ->with('warning', 'Pelunasan piutang berhasil dibatalkan.');
    }
}
