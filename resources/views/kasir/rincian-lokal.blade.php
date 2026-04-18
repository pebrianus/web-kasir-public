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
                    @if(!$isDataValid)
                        <div class="alert alert-warning shadow-sm border-left-warning">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <div>
                                    <strong>Note: </strong> Rincian tagihan telah dimodifikasi secara manual. Silakan tekan tombol <strong>Refresh Tagihan</strong> untuk mengambil ulang data dari SIMGOS.
                                </div>
                            </div>
                        </div>
                    @endif
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
                                <input type="hidden" name="jenis_kasir" value="{{ $jenis_kasir }}">

                                <button type="submit" class="btn btn-info btn-sm" title="Refresh Data SIMGOS">
                                    <i class="fas fa-sync"></i>
                                </button>
                            </form>

                            {{-- <a href="{{ route('kasir.tagihan.rincian.edit', ['id' => $head->id, 'jenis_kasir' => $jenis_kasir]) }}"
                                class="btn btn-warning btn-sm" title="Edit Rincian Tagihan">
                                <i class="fas fa-edit"></i>
                            </a> --}}
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
                    @elseif (in_array($head->status_kasir, ['lunas', 'piutang', 'outstanding']))
                        {{-- ALERT berbeda tergantung status --}}
                        @if ($head->status_kasir == 'piutang' || $head->status_kasir == 'outstanding')
                            <div class="alert alert-warning text-center">
                                <strong><i class="fas fa-clock"></i> PIUTANG</strong>
                            </div>
                        @else
                            <div class="alert alert-success text-center">
                                <strong><i class="fas fa-check-circle"></i> SUDAH LUNAS</strong>
                            </div>
                        @endif

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

                        <a href="{{ route('rincian.cetak.pasien', ['id' => $head->id, 'jenis_kasir' => $jenis_kasir]) }}"
                            target="_blank" class="btn btn-secondary btn-icon-split btn-block mb-2">
                            <span class="icon text-white-50"><i class="fas fa-file-invoice"></i></span>
                            <span class="text">Cetak Rincian Pasien</span>
                        </a>

                        <a href="{{ route('rincian.cetak.asuransi', ['id' => $head->id, 'jenis_kasir' => $jenis_kasir]) }}"
                            target="_blank" class="btn btn-secondary btn-icon-split btn-block mb-2">
                            <span class="icon text-white-50"><i class="fas fa-file-invoice"></i></span>
                            <span class="text">Cetak Rincian Asuransi</span>
                        </a>

                        <a href="{{ route('rincian.cetak.gabungan', ['id' => $head->id, 'jenis_kasir' => $jenis_kasir]) }}"
                            target="_blank" class="btn btn-secondary btn-icon-split btn-block mb-2">
                            <span class="icon text-white-50"><i class="fas fa-file-invoice"></i></span>
                            <span class="text">Cetak Rincian Gabungan</span>
                        </a>

                        <a href="{{ route('rincian.cetak.resep', ['id' => $head->id, 'jenis_kasir' => $jenis_kasir]) }}"
                            target="_blank" class="btn btn-secondary btn-icon-split btn-block mb-2">
                            <span class="icon text-white-50"> <i class="fas fa-capsules"></i> </span>
                            <span class="text">Cetak Resep</span>
                        </a>

                        <a href="{{ route('rincian.cetak.lab', ['id' => $head->id, 'jenis_kasir' => $jenis_kasir]) }}"
                            target="_blank" class="btn btn-secondary btn-icon-split btn-block mb-2">
                            <span class="icon text-white-50"> <i class="fas fa-vials"></i>
                            </span>
                            <span class="text">Cetak Rincian Lab</span>
                        </a>

                        <hr class="my-4">
                        <form action="{{ route('kasir.bayar.batal', ['id' => $head->id]) }}" method="POST"
                            onsubmit="return confirm('Pembayaran akan dihapus dari laporan harian dan status tagihan kembali menjadi DRAFT.\n\nApakah Anda yakin ingin membatalkan pembayaran ini?');">
                            @csrf
                            <input type="hidden" name="jenis_kasir" value="{{ $jenis_kasir }}">

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
    {{-- MODAL PEMBAYARAN (yang sudah ada) --}}
    <div class="modal fade" id="modalPembayaran" tabindex="-1" role="dialog" aria-labelledby="modalPembayaranLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                {{-- HAPUS action dari form, kita handle via JS --}}
                <form id="formPembayaran" action="{{ route('kasir.bayar-tagihan.store', ['id' => $head->id]) }}"
                    method="POST">
                    @csrf
                    <input type="hidden" name="jenis_kasir" value="{{ $jenis_kasir }}">
                    <input type="hidden" name="nominal_bayar" value="{{ $total_pasien_bersih }}">

                    <div class="modal-header">
                        <h5 class="modal-title" id="modalPembayaranLabel">Konfirmasi Pembayaran</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Total Tagihan Pasien</label>
                            <input type="text" class="form-control form-control-lg"
                                value="Rp {{ number_format($total_pasien_bersih, 2, ',', '.') }}" readonly>
                        </div>
                        <hr>
                        <div class="form-group">
                            <label for="metode_bayar_id">Metode Bayar</label>
                            <select class="form-control" id="metode_bayar_id" name="metode_bayar_id" required>
                                <option value="" selected disabled>-- Pilih Metode Bayar --</option>
                                @foreach ($metodeBayar as $metode)
                                    <option value="{{ $metode->TABEL_ID }}">{{ $metode->DESKRIPSI }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
                        {{-- Ubah type="button" agar kita kontrol via JS --}}
                        <button type="button" class="btn btn-primary" id="btnKonfirmasiPembayaran">
                            Konfirmasi Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ============================================== --}}
    {{-- MODAL PIUTANG ASURANSI (BARU) --}}
    {{-- ============================================== --}}
    <div class="modal fade" id="modalPiutang" tabindex="-1" role="dialog" aria-labelledby="modalPiutangLabel"
        aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-white" id="modalPiutangLabel">
                        <i class="fas fa-file-invoice-dollar mr-2"></i>
                        Pilih Piutang Asuransi
                    </h5>
                    <button class="close text-white" type="button" id="btnTutupModalPiutang" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Pilih tagihan asuransi yang akan dijadikan piutang untuk pembayaran ini:</p>

                    @php
                        // Filter detail yang ditanggung asuransi > 0
                        $itemAsuransi = $detail->filter(fn($i) => $i->nominal_ditanggung_asuransi > 0);
                    @endphp

                    @if ($itemAsuransi->isEmpty())
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Tidak ada tagihan yang ditanggung asuransi.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="40">
                                        </th>
                                        <th>Deskripsi Item</th>
                                        <th class="text-right">Qty</th>
                                        <th class="text-right">Ditanggung Asuransi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($itemAsuransi as $item)
                                        <tr class="row-piutang" style="cursor:pointer">
                                            <td>
                                                <input type="checkbox" class="chk-piutang" name="piutang_item_ids[]"
                                                    value="{{ $item->id }}" data-nominal="{{ $item->nominal_ditanggung_asuransi }}"
                                                    form="formPembayaran">
                                            </td>
                                            <td>{{ $item->deskripsi_item }}</td>
                                            <td class="text-right">{{ $item->qty }}</td>
                                            <td class="text-right text-primary font-weight-bold">
                                                Rp {{ number_format($item->nominal_ditanggung_asuransi, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-info">
                                        <td colspan="3" class="text-right font-weight-bold">Total Piutang Dipilih:</td>
                                        <td class="text-right font-weight-bold" id="totalPiutangDipilih">Rp 0</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        {{-- Hidden input untuk total piutang yang dipilih --}}
                        <input type="hidden" name="total_piutang_dipilih" id="inputTotalPiutang" form="formPembayaran"
                            value="0">
                    @endif
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" id="btnKembaliKePembayaran">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </button>
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-info" id="btnCheckAll" @if($itemAsuransi->isEmpty()) disabled
                        @endif>
                            <i class="fas fa-check-double mr-1"></i> Pilih Semua
                        </button>
                        <button type="button" class="btn btn-success ml-2" id="btnKonfirmasiPiutang"
                            @if($itemAsuransi->isEmpty()) disabled @endif>
                            <i class="fas fa-check mr-1"></i> Konfirmasi & Proses Piutang
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================== --}}
    {{-- JAVASCRIPT --}}
    {{-- ============================================== --}}
    @push('scripts')
        <script>
            $(document).ready(function () {

                const METODE_PIUTANG_ID = 4;

                // -----------------------------------------------
                // Tombol Konfirmasi di Modal Pembayaran
                // -----------------------------------------------
                $('#btnKonfirmasiPembayaran').on('click', function () {
                    const metodeTerpilih = parseInt($('#metode_bayar_id').val());

                    if (!metodeTerpilih) {
                        alert('Silakan pilih metode bayar terlebih dahulu.');
                        return;
                    }

                    if (metodeTerpilih === METODE_PIUTANG_ID) {
                        // Tutup modal pembayaran, buka modal piutang
                        $('#modalPembayaran').modal('hide');
                        $('#modalPembayaran').on('hidden.bs.modal', function () {
                            $('#modalPiutang').modal('show');
                            $(this).off('hidden.bs.modal'); // unbind agar tidak loop
                        });
                    } else {
                        // Submit form biasa
                        $('#formPembayaran').submit();
                    }
                });

                // -----------------------------------------------
                // Tombol Kembali dari Modal Piutang
                // -----------------------------------------------
                $('#btnKembaliKePembayaran').on('click', function () {
                    $('#modalPiutang').modal('hide');
                    $('#modalPiutang').on('hidden.bs.modal', function () {
                        $('#modalPembayaran').modal('show');
                        $(this).off('hidden.bs.modal');
                    });
                });

                // Tombol X di Modal Piutang = kembali ke Modal Pembayaran
                $('#btnTutupModalPiutang').on('click', function () {
                    $('#modalPiutang').modal('hide');
                    $('#modalPiutang').on('hidden.bs.modal', function () {
                        $('#modalPembayaran').modal('show');
                        $(this).off('hidden.bs.modal');
                    });
                });

                // -----------------------------------------------
                // Checkbox: Pilih Semua
                // -----------------------------------------------
                $('#checkAll').on('change', function () {
                    $('.chk-piutang').prop('checked', this.checked);
                    hitungTotalPiutang();
                });

                // -----------------------------------------------
                // Checkbox: Per Item — klik baris juga bisa
                // -----------------------------------------------
                $(document).on('click', '.row-piutang', function (e) {
                    if (!$(e.target).is('input[type=checkbox]')) {
                        const chk = $(this).find('.chk-piutang');
                        chk.prop('checked', !chk.prop('checked'));
                    }
                    hitungTotalPiutang();
                });

                $('.chk-piutang').on('change', function () {
                    hitungTotalPiutang();
                });

                // -----------------------------------------------
                // Hitung Total Piutang yang Dipilih
                // -----------------------------------------------
                function hitungTotalPiutang() {
                    let total = 0;
                    $('.chk-piutang:checked').each(function () {
                        total += parseFloat($(this).data('nominal')) || 0;
                    });

                    // Format angka Indonesia
                    const formatted = 'Rp ' + total.toLocaleString('id-ID', { minimumFractionDigits: 0 });
                    $('#totalPiutangDipilih').text(formatted);
                    $('#inputTotalPiutang').val(total);

                    // Disable tombol konfirmasi jika tidak ada yang dipilih
                    $('#btnKonfirmasiPiutang').prop('disabled', total === 0);
                }

                // -----------------------------------------------
                // Konfirmasi Piutang → Submit Form
                // -----------------------------------------------
                $('#btnKonfirmasiPiutang').on('click', function () {
                    const totalDipilih = parseFloat($('#inputTotalPiutang').val()) || 0;
                    if (totalDipilih === 0) {
                        alert('Pilih minimal satu item piutang asuransi.');
                        return;
                    }

                    if (confirm('Konfirmasi proses piutang asuransi sebesar Rp ' +
                        totalDipilih.toLocaleString('id-ID') + '?')) {
                        $('#formPembayaran').submit();
                    }
                });

            });

            $('#btnCheckAll').on('click', function () {
                const allChecked = $('.chk-piutang:not(:checked)').length === 0;
                $('.chk-piutang').prop('checked', !allChecked).trigger('change');
                $(this).html(allChecked
                    ? '<i class="fas fa-check-double mr-1"></i> Pilih Semua'
                    : '<i class="fas fa-times-circle mr-1"></i> Batal Semua'
                );
            });
        </script>
    @endpush


@endsection
