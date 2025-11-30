@extends('layouts.main')

@section('title', 'Rincian Tagihan: ' . $head->simgos_tagihan_id)

@section('content')

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @elseif (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @elseif (session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            {{ session('info') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Proses Gagal!</strong> Terjadi kesalahan validasi:
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Rincian Tagihan Kasir</h1>
        {{-- Tombol Cetak Kuitansi kita pindahkan ke sidebar kanan --}}
    </div>

    {{-- Wrapper Row untuk 2 Kolom --}}
    <div class="row">

        {{-- ==== KOLOM KIRI (70%) - RINCIAN TAGIHAN ==== --}}
        <div class="col-lg-8">

            {{-- ==== CARD DATA PASIEN (dari data snapshot) ==== --}}
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Nama Pasien</dt>
                                <dd class="col-sm-8 font-weight-bold">{{ $head->nama_pasien }}</dd>

                                <dt class="col-sm-4">NORM</dt>
                                <dd class="col-sm-8">{{ $head->simgos_norm }}</dd>

                                <dt class="col-sm-4">No. Tagihan</dt>
                                <dd class="col-sm-8">{{ $head->simgos_tagihan_id }}</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Ruangan</dt>
                                <dd class="col-sm-8">{{ $head->nama_ruangan }}</dd>

                                <dt class="col-sm-4">Dokter</dt>
                                <dd class="col-sm-8">{{ $head->nama_dokter }}</dd>

                                <dt class="col-sm-4">Penjamin</dt>
                                <dd class="col-sm-8">{{ $head->nama_asuransi }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ==== DAFTAR RINCIAN TAGIHAN (dari data snapshot) ==== --}}
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Rincian Tagihan</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Deskripsi Item</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Harga Satuan</th>
                                    <th class="text-right">Subtotal</th>
                                    <th class="text-right">Tangg. Asuransi</th>
                                    <th class="text-right">Tangg. Pasien</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $total_asli = 0;
                                    $total_asuransi = 0;
                                    $total_pasien = 0;
                                @endphp
                                @forelse ($detail as $item)
                                    <tr>
                                        <td>{{ $item->deskripsi_item }}</td>
                                        <td class="text-right">{{ $item->qty }}</td>
                                        <td class="text-right">
                                            {{ fmod($item->harga_satuan, 1) !== 0.0
                                                ? number_format($item->harga_satuan, 2, ',', '.')
                                                : number_format($item->harga_satuan, 0, ',', '.') }}
                                        </td>
                                        <td class="text-right">
                                            {{ fmod($item->subtotal, 1) !== 0.0
                                                ? number_format($item->subtotal, 2, ',', '.')
                                                : number_format($item->subtotal, 0, ',', '.') }}
                                        </td>
                                        <td class="text-right bg-light">
                                            {{ fmod($item->nominal_ditanggung_asuransi, 1) !== 0.0
                                                ? number_format($item->nominal_ditanggung_asuransi, 2, ',', '.')
                                                : number_format($item->nominal_ditanggung_asuransi, 0, ',', '.') }}
                                        </td>
                                        <td class="text-right bg-light">
                                            {{ fmod($item->nominal_ditanggung_pasien, 1) !== 0.0
                                                ? number_format($item->nominal_ditanggung_pasien, 2, ',', '.')
                                                : number_format($item->nominal_ditanggung_pasien, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    @php
                                        $total_asli += $item->subtotal;
                                        $total_asuransi += $item->nominal_ditanggung_asuransi;
                                        $total_pasien += $item->nominal_ditanggung_pasien;
                                    @endphp
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">Data rincian tidak ditemukan.</td>
                                    </tr>
                                    <div class="alert alert-info">
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="bg-light">
                                    <td colspan="3" class="text-right font-weight-bold">SUBTOTAL ASLI</td>
                                    <td colspan="3" class="text-right font-weight-bold">
                                        Rp.
                                        {{ fmod($total_asli, 1) !== 0.0
                                            ? number_format($total_asli, 2, ',', '.')
                                            : number_format($total_asli, 0, ',', '.') }}
                                    </td>
                                </tr>
                                {{-- +++ TAMBAHKAN INI UNTUK DISKON +++ --}}
                                @if ($head->diskon_simgos > 0)
                                    <tr class="text-danger">
                                        <td colspan="3" class="text-right font-weight-bold">POTONGAN / DISKON</td>
                                        <td colspan="3" class="text-right font-weight-bold">
                                            - Rp {{ number_format($head->diskon_simgos, 2, ',', '.') }}
                                        </td>
                                    </tr>

                                    {{-- Tampilkan Total Akhir setelah diskon --}}
                                    <tr class="bg-light">
                                        <td colspan="3" class="text-right font-weight-bold">TOTAL BERSIH (Setelah Diskon)
                                        </td>
                                        <td colspan="3" class="text-right font-weight-bold">
                                            Rp {{ number_format($total_asli - $head->diskon_simgos, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endif
                                {{-- +++ BATAS TAMBAHAN +++ --}}
                                <tr>
                                    <td colspan="3" class="text-right font-weight-bold">TOTAL DITANGGUNG ASURANSI</td>
                                    <td class="text-right font-weight-bold" colspan="3">
                                        Rp.
                                        {{ fmod($total_asuransi, 1) !== 0.0
                                            ? number_format($total_asuransi, 2, ',', '.')
                                            : number_format($total_asuransi, 0, ',', '.') }}
                                    </td>
                                </tr>
                                <tr class="table-success">
                                    <td colspan="3" class="text-right font-weight-bold">TOTAL DITANGGUNG PASIEN</td>
                                    <td class="text-right font-weight-bold" colspan="3">
                                        Rp.
                                        @php
                                            // Hitung nilai bersih: Total Item Pasien - Diskon Global
                                            $total_pasien_bersih = max(0, $total_pasien - $head->diskon_simgos);
                                        @endphp
                                        {{ fmod($total_pasien_bersih, 1) !== 0.0
                                            ? number_format($total_pasien_bersih, 2, ',', '.')
                                            : number_format($total_pasien_bersih, 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div> {{-- ==== END KOLOM KIRI ==== --}}
        {{-- ==== KOLOM KANAN (30%) - SIDEBAR AKSI ==== --}}
        <div class="col-lg-4">

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Panel Aksi</h6>
                    <div>
                        {{-- Tombol Refresh (Hanya jika masih draft) --}}
                        @if ($head->status_kasir == 'draft')
                            <form action="{{ route('kasir.tagihan.refresh', ['id' => $head->id]) }}" method="POST"
                                class="d-inline"
                                onsubmit="return confirm('Anda yakin ingin me-refresh data dari SIMGOS? Pembagian tagihan akan direset.');">
                                @csrf
                                <button type="submit" class="btn btn-info btn-sm" title="Refresh Data SIMGOS">
                                    <i class="fas fa-sync"></i>
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('kasir.pasien.tagihan', ['norm' => $head->simgos_norm, 'jenis_kasir' => $jenis_kasir]) }}"
                            class="btn btn-danger btn-sm" title="Kembali">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">

                    {{-- LOGIKA IF/ELSE UNTUK TOMBOL --}}
                    {{-- FIX ERROR NANTI --}}
                    @if ($head->status_kasir == 'draft')
                        {{-- JIKA MASIH DRAFT: Tampilkan tombol proses --}}

                        <a href="{{ route('kasir.tagihan.bagi', ['id' => $head->id, 'jenis_kasir' => request('jenis_kasir')]) }}"
                            class="btn btn-primary btn-icon-split btn-block mb-2">
                            <span class="icon text-white-50"><i class="fas fa-divide"></i></span>
                            <span class="text">Bagi Tagihan</span>
                        </a>

                        {{-- Tombol untuk memicu Modal Pembayaran --}}
                        <button type="button" class="btn btn-warning btn-icon-split btn-block" data-toggle="modal"
                            data-target="#modalPembayaran">
                            <span class="icon text-white-50"><i class="fas fa-dollar-sign"></i></span>
                            <span class="text">Proses Pembayaran</span>
                        </button>
                    @else
                        {{-- JIKA SUDAH LUNAS: Tampilkan tombol cetak --}}

                        <div class="alert alert-success text-center">
                            <strong><i class="fas fa-check-circle"></i> SUDAH LUNAS</strong>
                        </div>

                        <a href="{{ route('kuitansi.cetak.pasien', ['id' => $head->id, 'jenis_kasir' => $jenis_kasir]) }}"
                            target="_blank" {{-- Buka di tab baru --}} class="btn btn-success btn-icon-split btn-block mb-2">
                            <span class="icon text-white-50"><i class="fas fa-print"></i></span>
                            <span class="text">Cetak Kuitansi Pasien</span>
                        </a>

                        <a href="{{ route('kuitansi.cetak.asuransi', ['id' => $head->id, 'jenis_kasir' => $jenis_kasir]) }}"
                            target="_blank" {{-- Buka di tab baru --}} class="btn btn-info btn-icon-split btn-block mb-2">
                            <span class="icon text-white-50"><i class="fas fa-print"></i></span>
                            <span class="text">Cetak Kuitansi Asuransi</span>
                        </a>

                        <hr class="my-4">
                        <form action="{{ route('kasir.bayar.batal', ['id' => $head->id]) }}" method="POST"
                            onsubmit="return confirm('Pembayaran akan dihapus dari laporan harian dan status tagihan kembali menjadi DRAFT.\n\nApakah Anda yakin ingin membatalkan pembayaran ini?');">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-block">
                                <i class="fas fa-undo-alt mr-1"></i> Batalkan Pembayaran
                            </button>
                        </form>
                    @endif
                    {{-- AKHIR LOGIKA IF/ELSE --}}

                </div>
            </div>

        </div> {{-- ==== END KOLOM KANAN ==== --}}

    </div> {{-- ==== END ROW ==== --}}

    {{-- MODAL PEMBAYARAN --}}
    <div class="modal fade" id="modalPembayaran" tabindex="-1" role="dialog" aria-labelledby="modalPembayaranLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('kasir.bayar-tagihan.store', ['id' => $head->id]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="jenis_kasir" value="{{ $jenis_kasir }}">

                    <div class="modal-header">
                        <h5 class="modal-title" id="modalPembayaranLabel">Konfirmasi Pembayaran</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">

                        {{-- Ambil total pasien dari tfoot tabel --}}
                        @php
                            $total_pasien = 0;
                            foreach ($detail as $item) {
                                $total_pasien += $item->nominal_ditanggung_pasien;
                            }
                        @endphp

                        <div class="form-group">
                            <label>Total Tagihan Pasien</label>
                            <input type="text" class="form-control form-control-lg" id="total-tagihan-pasien"
                                value="Rp {{ number_format($total_pasien_bersih, 2, ',', '.') }}" readonly>
                        </div>

                        <hr>

                        <div class="form-group">
                            <label for="metode_bayar_id">Metode Bayar</label>
                            <select class="form-control" id="metode_bayar_id" name="metode_bayar_id" required>
                                <option value="" selected disabled>-- Pilih Metode Bayar --</option>
                                @foreach ($metodeBayar as $metode)
                                    {{-- Kita pakai 'TABEL_ID' sebagai value, sesuai skema Anda --}}
                                    <option value="{{ $metode->TABEL_ID }}">{{ $metode->DESKRIPSI }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="nominal_bayar" value="{{ $total_pasien }}">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
                        <button class="btn btn-primary" type="submit">Konfirmasi Pembayaran</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
