@extends('layouts.main')

@section('title', 'Detail Piutang: ' . $piutang->nama_pasien)

@section('content')

    {{-- ── FLASH MESSAGES ── --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @elseif (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @elseif (session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle mr-1"></i> {{ session('info') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong><i class="fas fa-times-circle"></i> Proses Gagal!</strong> Terjadi kesalahan validasi:
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <style>
        .table-hover tbody tr {
            transition: background-color 0.3s ease;
        }
    </style>

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-file-invoice-dollar mr-2 text-primary"></i>Rincian Tagihan Piutang
        </h1>
        <div class="d-flex">
            <a href="{{ route('kasir.tagihan.lokal', ['id' => $piutang->kasir_tagihan_head_id, 'jenis_kasir' => $jenis_kunjungan]) }}"
                class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-file-invoice mr-1"></i> Ke Halaman Tagihan
            </a>
            <a href="{{ route('piutang.index') }}" class="btn btn-sm btn-outline-secondary ml-2">
                <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar Piutang
            </a>
        </div>
    </div>

    <div class="row">

        {{-- ════════════════════════════════════════════
        KOLOM KIRI — Rincian Tagihan
        ════════════════════════════════════════════ --}}
        <div class="col-lg-8">

            {{-- Card Info Pasien --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user-circle mr-1"></i> Data Pasien
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row mb-0">
                                <dt class="col-sm-5 text-muted">Nama Pasien</dt>
                                <dd class="col-sm-7 font-weight-bold">{{ $tagihanHead->nama_pasien }}</dd>

                                <dt class="col-sm-5 text-muted">No. RM</dt>
                                <dd class="col-sm-7">{{ $tagihanHead->simgos_norm }}</dd>

                                <dt class="col-sm-5 text-muted">No. Tagihan</dt>
                                <dd class="col-sm-7">{{ $tagihanHead->simgos_tagihan_id }}</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row mb-0">
                                <dt class="col-sm-5 text-muted">Ruangan</dt>
                                <dd class="col-sm-7">{{ $tagihanHead->nama_ruangan }}</dd>

                                <dt class="col-sm-5 text-muted">Dokter</dt>
                                <dd class="col-sm-7">{{ $tagihanHead->nama_dokter }}</dd>

                                <dt class="col-sm-5 text-muted">Penjamin</dt>
                                <dd class="col-sm-7">{{ $tagihanHead->nama_asuransi }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card Rincian Item Tagihan --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list-alt mr-1"></i> Rincian Item Tagihan
                    </h6>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th class="px-2">Deskripsi Item</th>
                                    <th class="text-center" width="50">Qty</th>
                                    <th class="text-right" width="130">Harga Satuan</th>
                                    <th class="text-right" width="130">Subtotal</th>
                                    <th class="text-right" width="130">Tangg. Asuransi</th>
                                    <th class="text-right px-2" width="130">Tangg. Pasien</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $total_asli = 0;
                                    $total_asuransi = 0;
                                    $total_pasien = 0;

                                    // Helper: format angka tanpa desimal jika bulat
                                    function fmtRp($val)
                                    {
                                        return fmod((float) $val, 1) !== 0.0
                                            ? number_format($val, 2, ',', '.')
                                            : number_format($val, 0, ',', '.');
                                    }
                                @endphp

                                @forelse ($tagihanDetail as $item)
                                    <tr>
                                        <td class="px-2">{{ $item->deskripsi_item }}

                                        </td>
                                        <td class="text-center">{{ $item->qty }}</td>
                                        <td class="text-right">{{ fmtRp($item->harga_satuan) }}</td>
                                        <td class="text-right">{{ fmtRp($item->subtotal) }}</td>
                                        <td class="text-right bg-light">{{ fmtRp($item->nominal_ditanggung_asuransi) }}
                                        </td>
                                        <td class="text-right bg-light px-2">{{ fmtRp($item->nominal_ditanggung_pasien) }}
                                        </td>
                                    </tr>
                                    @php
                                        $total_asli += $item->subtotal;
                                        $total_asuransi += $item->nominal_ditanggung_asuransi;
                                        $total_pasien += $item->nominal_ditanggung_pasien;
                                    @endphp
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">
                                            <i class="fas fa-inbox mr-1"></i> Data rincian tidak ditemukan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                {{-- Subtotal --}}
                                <tr class="bg-light">
                                    <td colspan="3" class="text-right font-weight-bold">SUBTOTAL</td>
                                    <td class="text-right font-weight-bold">Rp {{ fmtRp($total_asli) }}</td>
                                    <td colspan="2"></td>
                                </tr>

                                {{-- Diskon jika ada --}}
                                @if ($tagihanHead->diskon_simgos > 0)
                                    <tr class="text-danger">
                                        <td colspan="3" class="text-right font-weight-bold">POTONGAN / DISKON</td>
                                        <td class="text-right font-weight-bold">
                                            &minus; Rp {{ fmtRp($tagihanHead->diskon_simgos) }}
                                        </td>
                                        <td colspan="2"></td>
                                    </tr>
                                    <tr class="bg-light">
                                        <td colspan="3" class="text-right font-weight-bold">TOTAL BERSIH (Setelah Diskon)
                                        </td>
                                        <td class="text-right font-weight-bold">
                                            Rp {{ fmtRp($total_asli - $tagihanHead->diskon_simgos) }}
                                        </td>
                                        <td colspan="2"></td>
                                    </tr>
                                @endif

                                {{-- Total asuransi --}}
                                <tr>
                                    <td colspan="4" class="text-right font-weight-bold">TOTAL DITANGGUNG ASURANSI</td>
                                    <td class="text-right font-weight-bold">Rp {{ fmtRp($total_asuransi) }}</td>
                                    <td></td>
                                </tr>

                                {{-- Total pasien --}}
                                @php
                                    $total_pasien_bersih = max(0, $total_pasien - $tagihanHead->diskon_simgos);
                                @endphp
                                <tr class="table-success">
                                    <td colspan="5" class="text-right font-weight-bold">TOTAL DITANGGUNG PASIEN</td>
                                    <td class="text-right font-weight-bold">Rp {{ fmtRp($total_pasien_bersih) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Card Riwayat Pembayaran Piutang --}}
            <div class="card shadow mb-4 overflow-hidden">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history mr-1"></i> Riwayat Pembayaran
                    </h6>
                    <span class="badge badge-pill badge-primary">{{ $riwayatBayar->count() }} transaksi</span>
                </div>
                <div class="card-body p-0">

                    @if ($riwayatBayar->isEmpty())
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            Belum ada riwayat pembayaran.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="pl-3">#</th>
                                        <th>Tanggal Bayar</th>
                                        <th>Nominal Bayar</th>
                                        <th>Sisa Sebelum</th>
                                        <th>Sisa Sesudah</th>
                                        <th>Status</th>
                                        <th>Oleh</th>
                                        <th>Keterangan</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $lastId = $riwayatBayar->max('id'); @endphp

                                    @foreach ($riwayatBayar as $i => $bayar)
                                        <tr style="{{ $bayar->id === $lastId ? 'background-color: #f0f7ff;' : '' }}">
                                            <td class="pl-3 text-muted">{{ $i + 1 }}</td>
                                            <td>{{ $bayar->tanggal_bayar->format('d/m/Y') }}</td>
                                            <td class="text-success">Rp {{ fmtRp($bayar->nominal_bayar) }}</td>
                                            <td class="text-muted">Rp {{ fmtRp($bayar->nominal_sisa_sebelum) }}</td>
                                            <td>Rp {{ fmtRp($bayar->nominal_sisa_sesudah) }}</td>
                                            <td>
                                                @if ($bayar->status_sesudah === 'lunas')
                                                    <span class="badge badge-success">Lunas</span>
                                                @elseif ($bayar->status_sesudah === 'sebagian')
                                                    <span class="badge badge-warning">Sebagian</span>
                                                @else
                                                    <span class="badge badge-secondary">Outstanding</span>
                                                @endif
                                            </td>
                                            <td>{{ $bayar->user->name ?? '-' }}</td>
                                            <td class="text-muted small">{{ $bayar->keterangan ?? '-' }}</td>
                                            <td class="text-right pr-3">
                                                {{-- Tombol batal hanya di pembayaran terakhir --}}
                                                @if ($bayar->id === $lastId && $piutang->status !== 'lunas')
                                                    <form action="{{ route('piutang.pembayaran.batal', $bayar->id) }}" method="POST"
                                                        onsubmit="return confirm('Yakin ingin membatalkan pembayaran ini? Saldo piutang akan dikembalikan.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                                            <i class="fas fa-undo mr-1"></i> Batal
                                                        </button>
                                                    </form>
                                                @elseif ($bayar->id === $lastId && $piutang->status === 'lunas')
                                                    {{-- Piutang sudah lunas, tetap boleh dibatalkan --}}
                                                    <form action="{{ route('piutang.pembayaran.batal', $bayar->id) }}" method="POST"
                                                        onsubmit="return confirm('Piutang ini sudah lunas. Yakin ingin membatalkan pembayaran terakhir?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-undo mr-1"></i> Batal
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                </div>
            </div>

        </div>
        {{-- ════════════════════════════════════════════
        KOLOM KANAN — Panel Aksi
        ════════════════════════════════════════════ --}}
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-hand-holding-usd mr-1"></i> Panel Aksi Piutang
                    </h6>
                    {{-- Badge Status --}}
                    @php
                        switch ($piutang->status) {
                            case 'lunas':
                                $badgeClass = 'badge-success';
                                $statusLabel = 'Lunas';
                                break;
                            case 'sebagian':
                                $badgeClass = 'badge-warning';
                                $statusLabel = 'Sebagian';
                                break;
                            case 'outstanding':
                                $badgeClass = 'badge-danger';
                                $statusLabel = 'Outstanding';
                                break;
                            default:
                                $badgeClass = 'badge-secondary';
                                $statusLabel = ucfirst($piutang->status);
                                break;
                        }
                    @endphp
                    <span class="badge {{ $badgeClass }} badge-pill px-3 py-2">{{ $statusLabel }}</span>
                </div>

                <div class="card-body">

                    @if ($piutang->status === 'lunas')
                        {{-- ── SUDAH LUNAS ── --}}
                        <div class="text-center py-3">
                            <i class="fas fa-check-circle fa-3x text-success mb-2"></i>
                            <div class="font-weight-bold text-success h5">PIUTANG LUNAS</div>
                            <div class="text-muted small">
                                Dilunasi pada
                                {{ $piutang->tanggal_lunas ? $piutang->tanggal_lunas->format('d/m/Y') : '-' }}
                            </div>
                        </div>
                        <hr>
                        {{-- Tombol cetak kuitansi pelunasan (sesuaikan route jika ada) --}}
                        {{--
                        <a href="{{ route('piutang.cetak', $piutang->id) }}" target="_blank"
                            class="btn btn-success btn-icon-split btn-block mb-2">
                            <span class="icon text-white-50"><i class="fas fa-print"></i></span>
                            <span class="text">Cetak Bukti Lunas</span>
                        </a>
                        --}}
                        {{-- Tombol Batal Lunas --}}
                        <button type="button" class="btn btn-danger btn-icon-split btn-block mb-3" data-toggle="modal"
                            data-target="#modalBatalLunas">
                            <span class="icon text-white-50"><i class="fas fa-times-circle"></i></span>
                            <span class="text">Batalkan Pelunasan</span>
                        </button>
                    @else
                        {{-- ── BELUM LUNAS: TAMPILKAN FORM BAYAR ── --}}

                        {{-- Ringkasan sisa piutang --}}
                        <div class="alert alert-warning py-2 text-center mb-3">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Sisa Piutang yang Harus Dibayar</div>
                            <div class="h4 font-weight-bold text-danger mb-0">
                                Rp {{ fmtRp($piutang->nominal_sisa) }}
                            </div>
                        </div>

                        {{-- Tombol Bayar Sebagian --}}
                        <button type="button" class="btn btn-warning btn-icon-split btn-block mb-2" data-toggle="modal"
                            data-target="#modalBayarSebagian">
                            <span class="icon text-white-50"><i class="fas fa-coins"></i></span>
                            <span class="text">Bayar Sebagian</span>
                        </button>

                        {{-- Tombol Lunasi Penuh --}}
                        <button type="button" class="btn btn-primary btn-icon-split btn-block mb-3" data-toggle="modal"
                            data-target="#modalLunasi">
                            <span class="icon text-white-50"><i class="fas fa-check-double"></i></span>
                            <span class="text">Lunasi Penuh</span>
                        </button>

                        <hr>

                        {{-- Tombol Tandai Lunas Manual (tanpa bayar, misal: dihapusbukukan) --}}
                        <form action="{{ route('piutang.hapusbuku', $piutang->id) }}" method="POST"
                            onsubmit="return confirm('Piutang akan ditandai lunas tanpa pembayaran (hapus buku). Lanjutkan?');">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-outline-secondary btn-block btn-sm">
                                <i class="fas fa-eraser mr-1"></i> Hapus Buku (Write-off)
                            </button>
                        </form>
                    @endif

                </div>
            </div>

            {{-- Card Info Piutang --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle mr-1"></i> Info Piutang
                    </h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted pl-3" width="50%">Nama Asuransi</td>
                                <td class="font-weight-bold pr-3">{{ $piutang->nama_asuransi }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted pl-3">Total Tagihan Asuransi</td>
                                <td class="font-weight-bold pr-3">Rp {{ fmtRp($piutang->total_tagihan_asuransi) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted pl-3">Nominal Piutang</td>
                                <td class="font-weight-bold pr-3">Rp {{ fmtRp($piutang->nominal_piutang) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted pl-3">Terbayar</td>
                                <td class="font-weight-bold text-success pr-3">Rp {{ fmtRp($piutang->nominal_terbayar) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted pl-3">Sisa</td>
                                <td class="font-weight-bold text-danger pr-3">Rp {{ fmtRp($piutang->nominal_sisa) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted pl-3">Jatuh Tempo</td>
                                <td class="pr-3">
                                    {{ $piutang->tanggal_jatuh_tempo ? \Carbon\Carbon::parse($piutang->tanggal_jatuh_tempo)->format('d/m/Y') : '-' }}
                                    @if ($piutang->isJatuhTempo())
                                        <span class="badge badge-danger ml-1">Lewat</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted pl-3">Dicatat oleh</td>
                                <td class="pr-3">{{ $piutang->user ? $piutang->user->name : '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    {{-- ════════════════════════════════════════════
    MODAL: BAYAR SEBAGIAN
    ════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalBayarSebagian" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('piutang.bayar', $piutang->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="tipe" value="sebagian">

                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-coins mr-1"></i> Bayar Sebagian Piutang
                        </h5>
                        <button class="close" type="button" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">

                        <div class="alert alert-info py-2 text-center">
                            <small class="d-block text-muted">Sisa Piutang</small>
                            <strong class="h5">Rp {{ fmtRp($piutang->nominal_sisa) }}</strong>
                        </div>

                        <div class="form-group">
                            <label for="nominal_bayar">Nominal yang Dibayarkan <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input type="number" class="form-control" id="nominal_bayar" name="nominal_bayar" min="1"
                                    max="{{ $piutang->nominal_sisa }}" step="0.01" placeholder="0" required>
                            </div>
                            <small class="form-text text-muted">
                                Maksimal: Rp {{ fmtRp($piutang->nominal_sisa) }}
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="keterangan_bayar">Keterangan <small class="text-muted">(opsional)</small></label>
                            <textarea class="form-control" id="keterangan_bayar" name="keterangan" rows="2"
                                placeholder="Contoh: Transfer BCA tgl 01/07/2025..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="tanggal_bayar">Tanggal Bayar <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tanggal_bayar" name="tanggal_bayar"
                                value="{{ now()->toDateString() }}" required>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
                        <button class="btn btn-warning" type="submit">
                            <i class="fas fa-coins mr-1"></i> Simpan Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════
    MODAL: LUNASI PENUH
    ════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalLunasi" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('piutang.bayar', $piutang->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="tipe" value="lunas">
                    <input type="hidden" name="nominal_bayar" value="{{ $piutang->nominal_sisa }}">

                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-check-double mr-1"></i> Konfirmasi Pelunasan Penuh
                        </h5>
                        <button class="close text-white" type="button" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">

                        <div class="text-center py-2">
                            <i class="fas fa-file-invoice-dollar fa-3x text-primary mb-2"></i>
                            <p>Anda akan melunasi seluruh sisa piutang atas nama:</p>
                            <div class="h5 font-weight-bold">{{ $piutang->nama_pasien }}</div>
                            <div class="text-muted small mb-3">{{ $piutang->nama_asuransi }}</div>
                            <div class="alert alert-primary py-2">
                                <small class="d-block">Jumlah yang Dilunasi</small>
                                <strong class="h4">Rp {{ fmtRp($piutang->nominal_sisa) }}</strong>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="tanggal_lunas_input">Tanggal Lunas <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tanggal_lunas_input" name="tanggal_bayar"
                                value="{{ now()->toDateString() }}" required>
                        </div>

                        <div class="form-group">
                            <label for="keterangan_lunas">Keterangan <small class="text-muted">(opsional)</small></label>
                            <textarea class="form-control" id="keterangan_lunas" name="keterangan" rows="2"
                                placeholder="Contoh: Pelunasan via transfer..."></textarea>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-check mr-1"></i> Ya, Lunasi Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    {{-- Modal Batal Lunas --}}
    <div class="modal fade" id="modalBatalLunas" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Konfirmasi Batalkan Pelunasan</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin membatalkan pelunasan piutang ini?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle"></i>
                        Status akan <strong>dikembalikan</strong> dan pembayaran akan direset.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <form action="{{ route('piutang.batallunas', $piutang->id) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-danger">Ya, Batalkan Pelunasan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.getElementById('nominal_bayar').addEventListener('input', function () {
                const max = parseFloat(this.max);
                const val = parseFloat(this.value);
                if (val > max) this.value = max;
                if (val < 0) this.value = 0;
            });
        </script>
    @endpush

@endsection
