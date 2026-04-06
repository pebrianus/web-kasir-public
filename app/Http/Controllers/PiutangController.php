<?php

namespace App\Http\Controllers;

use App\Models\KasirPembayaran;
use App\Models\KasirPiutangPembayaran;
use App\Models\KasirSesi;
use App\Models\KasirTagihanPiutang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        return view('piutang.index', compact('data', 'statusFilter', 'search'));
    }

    // Detail piutang
    public function detailPiutang($id)
    {
        $piutang = KasirTagihanPiutang::with([
            'tagihanHead.details',
            'user',
            'pembayaran.user',  // eager load sekaligus user yang bayar
        ])
            ->findOrFail($id);

        $tagihanHead = $piutang->tagihanHead;
        $tagihanDetail = $tagihanHead ? $tagihanHead->details : collect();
        $riwayatBayar = $piutang->pembayaran->sortBy('id')->values();

        $jenis_kunjungan = DB::connection('simgos_pembayaran')
            ->table('tagihan as t')
            ->join('tagihan_pendaftaran as tp', function ($join) {
                $join->on('tp.TAGIHAN', '=', 't.ID')
                    ->where('tp.STATUS', 1)
                    ->where('tp.UTAMA', 1);
            })
            ->join('pendaftaran.kunjungan as k', 'k.NOPEN', '=', 'tp.PENDAFTARAN')
            ->join('master.ruangan as r', 'r.ID', '=', 'k.RUANGAN')
            ->where('t.ID', $piutang->simgos_tagihan_id)
            ->orderBy('k.MASUK', 'asc') // ambil kunjungan terbaru jika ada duplikat
            ->value('r.JENIS_KUNJUNGAN');

        return view('piutang.detail', compact('piutang', 'tagihanHead', 'tagihanDetail', 'riwayatBayar', 'jenis_kunjungan'));
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

        // Validasi sesi kasir aktif
        $sesiAktif = KasirSesi::where('status', 'BUKA')->latest()->first();
        if (!$sesiAktif) {
            return redirect()->back()->with('error', 'Tidak ada sesi kasir yang aktif. Silakan buka sesi terlebih dahulu.');
        }

        DB::transaction(function () use ($request, $piutang, $sesiAktif) {
            $piutang->tambahPembayaran(
                (float) $request->nominal_bayar,
                $request->keterangan,
            );

            // Refresh agar status terbaru dari DB terbaca
            $piutang->refresh();

            if ($piutang->status === 'lunas') {
                // Update status tagihan head → lunas
                if ($piutang->tagihanHead) {
                    $piutang->tagihanHead->update(['status_kasir' => 'lunas']);
                }

                // Rekap ke kasir_pembayaran dengan total keseluruhan piutang
                KasirPembayaran::create([
                    'kasir_tagihan_head_id' => $piutang->kasir_tagihan_head_id,
                    'user_id' => Auth::id(),
                    'metode_bayar_id' => 4, // piutang asuransi
                    'nominal_bayar' => $piutang->nominal_piutang, // total keseluruhan, bukan cicilan
                    'kasir_sesi_id' => $sesiAktif->id,
                ]);
            }
        });

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
    public function batalLunasPiutang(Request $request, $id)
    {
        $piutang = KasirTagihanPiutang::with('tagihanHead')->findOrFail($id);

        if ($piutang->status !== 'lunas') {
            return redirect()->route('piutang.detail', $id)
                ->with('error', 'Piutang ini belum berstatus lunas, tidak dapat dibatalkan.');
        }

        $lastPembayaran = $piutang->pembayaran()->latest('id')->first();
        if (!$lastPembayaran) {
            return redirect()->route('piutang.detail', $id)
                ->with('error', 'Tidak ada history pembayaran yang dapat dibatalkan.');
        }

        if ($lastPembayaran->status_sesudah !== 'lunas') {
            return redirect()->route('piutang.detail', $id)
                ->with('error', 'Pembayaran terakhir bukan pelunasan, gunakan batal pembayaran cicilan.');
        }

        DB::transaction(function () use ($piutang, $lastPembayaran) {
            // Restore saldo piutang
            $piutang->nominal_terbayar = (float) $piutang->nominal_terbayar - (float) $lastPembayaran->nominal_bayar;
            $piutang->nominal_sisa = (float) $lastPembayaran->nominal_sisa_sebelum;
            $piutang->status = $lastPembayaran->status_sebelum;
            $piutang->tanggal_lunas = null;
            $keteranganLama = $piutang->keterangan ? $piutang->keterangan . ' | ' : '';
            $piutang->keterangan = $keteranganLama
                . 'Pelunasan dibatalkan oleh ' . auth()->user()->nama
                . ' pada ' . now()->format('d/m/Y H:i');
            $piutang->save();

            // Hapus history pembayaran terakhir
            $lastPembayaran->delete();

            // Sinkronkan status_kasir di tagihan head
            if ($piutang->tagihanHead) {
                $piutang->tagihanHead->update([
                    'status_kasir' => $piutang->status === 'outstanding' ? 'outstanding' : 'piutang',
                ]);
            }

            // Hapus rekap di kasir_pembayaran jika ada
            KasirPembayaran::where('kasir_tagihan_head_id', $piutang->kasir_tagihan_head_id)
                ->where('metode_bayar_id', 4)
                ->delete();
        });

        return redirect()->route('piutang.detail', $id)
            ->with('warning', 'Pelunasan piutang berhasil dibatalkan.');
    }

    public function batalPembayaran(KasirPiutangPembayaran $pembayaran)
    {
        $piutang = $pembayaran->piutang;

        // Guard: hanya boleh batal pembayaran paling terakhir
        $lastPembayaran = $piutang->pembayaran()->latest('id')->first();
        if ($lastPembayaran->id !== $pembayaran->id) {
            return back()->with('error', 'Hanya pembayaran terakhir yang dapat dibatalkan.');
        }

        // Kembalikan saldo piutang
        $piutang->nominal_terbayar = (float) $piutang->nominal_terbayar - (float) $pembayaran->nominal_bayar;
        $piutang->nominal_sisa = $pembayaran->nominal_sisa_sebelum;
        $piutang->status = $pembayaran->status_sebelum;
        $piutang->tanggal_lunas = null;
        $piutang->save();

        // Jika sebelumnya lunas, kembalikan status_kasir di tagihan head
        if ($pembayaran->status_sesudah === 'lunas' && $piutang->tagihanHead) {
            $piutang->tagihanHead->update(['status_kasir' => 'piutang']);

            // Hapus rekap di kasir_pembayaran jika ada
            KasirPembayaran::where('kasir_tagihan_head_id', $piutang->kasir_tagihan_head_id)
                ->where('metode_bayar_id', 4)
                ->delete();
        }

        $pembayaran->delete();

        return redirect()
            ->route('piutang.detail', $piutang->id)
            ->with('success', 'Pembayaran berhasil dibatalkan, saldo piutang telah dikembalikan.');
    }
}
