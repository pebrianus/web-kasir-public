@extends('layouts.main')

@section('title', 'Detail Tagihan Farmasi')

@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            Detail Tagihan Farmasi
        </h1>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
        </div>
    @endif

    <div class="row">

        {{-- ===== KOLOM KIRI (70%) ===== --}}
        <div class="col-lg-8">

            {{-- CARD INFORMASI --}}
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>No. Transaksi:</strong> {{ $tagihan->simgos_penjualan_id }}</p>
                            <p><strong>Nama Pasien:</strong> {{ $tagihan->nama_pengunjung }}</p>
                            <p>
                                <strong>Nama Dokter:</strong>
                                {{ !empty($tagihan->nama_dokter) ? $tagihan->nama_dokter : '-' }}
                            </p>
                        </div>

                        <div class="col-md-6">
                            <p>
                                <strong>Tanggal:</strong>
                                {{ \Carbon\Carbon::parse($tagihan->simgos_tanggal)->format('d-m-Y H:i') }}
                            </p>

                            <p>
                                <strong>Status:</strong>
                                @if ($tagihan->status_kasir == 'lunas')
                                    <span class="badge badge-success">Selesai / Lunas</span>
                                @else
                                    <span class="badge badge-warning">Belum Lunas</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CARD RINCIAN --}}
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Rincian Obat
                    </h6>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%">
                            <thead>
                                <tr>
                                    <th>Nama Obat</th>
                                    <th width="100" class="text-center">Qty</th>
                                    <th width="150" class="text-right">Harga</th>
                                    <th width="150" class="text-right">Subtotal</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($tagihan->details as $item)
                                    <tr>
                                        <td>{{ $item->nama_barang }}</td>
                                        <td class="text-center">
                                            {{ rtrim(rtrim($item->qty, '0'), '.') }}
                                        </td>
                                        <td class="text-right">
                                            Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}
                                        </td>
                                        <td class="text-right">
                                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            Tidak ada detail obat
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            <tfoot>
                                <tr class="bg-light">
                                    <th colspan="3" class="text-right">TOTAL</th>
                                    <th class="text-right">
                                        Rp {{ number_format($tagihan->total_tagihan, 0, ',', '.') }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        {{-- ===== KOLOM KANAN (30%) ===== --}}
        <div class="col-lg-4">

            <div class="card shadow mb-4">

                {{-- HEADER (1 baris saja) --}}
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Panel Aksi</h6>

                    <a href="{{ route('farmasi.index') }}" class="btn btn-danger btn-sm">
                        <i class="fas fa-times"></i>
                    </a>
                </div>

                {{-- BODY --}}
                <div class="card-body">

                    @if ($tagihan->status_kasir != 'lunas')
                        <button type="button" class="btn btn-success btn-block mb-3" data-toggle="modal"
                            data-target="#modalBayar">
                            <i class="fas fa-cash-register mr-1"></i>
                            Proses Pembayaran
                        </button>
                    @endif


                    <a href="{{ route('farmasi.cetakKuitansi', $tagihan->simgos_penjualan_id) }}"
                        class="btn btn-primary btn-block mb-3" target="_blank">
                        <i class="fas fa-print mr-1"></i>
                        Cetak Kuitansi
                    </a>


                    @if ($tagihan->status_kasir == 'lunas')

                        <form action="{{ route('farmasi.batal', $tagihan->simgos_penjualan_id) }}" method="POST"
                            onsubmit="return confirm('Pembayaran akan dibatalkan.\n\nStatus tagihan akan kembali menjadi DRAFT.\n\nApakah Anda yakin ingin melanjutkan?');">

                            @csrf
                            @method('POST')

                            <button type="submit" class="btn btn-outline-danger btn-block">
                                <i class="fas fa-undo-alt mr-1"></i>
                                Batalkan Pembayaran
                            </button>

                        </form>

                    @endif

                </div>

            </div>

        </div>

    </div>
    <div class="modal fade" id="modalBayar" tabindex="-1" role="dialog" aria-labelledby="modalBayarLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold text-success" id="modalBayarLabel">
                        <i class="fas fa-question-circle mr-1"></i> Konfirmasi Pembayaran
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form action="{{ route('farmasi.bayar', $tagihan->simgos_penjualan_id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="mb-4 text-gray-800">Apakah data penjualan sudah benar?</p>

                        <table class="table table-borderless table-sm">
                            <tr>
                                <th width="30%" class="text-left">Nama</th>
                                <td width="5%">:</td>
                                <td><strong>{{ $tagihan->nama_pengunjung }}</strong></td>
                            </tr>
                            <tr>
                                <th class="text-left">Total</th>
                                <td>:</td>
                                <td>
                                    <strong class="text-success" style="font-size: 1.2rem;">
                                        Rp {{ number_format($tagihan->total_tagihan, 0, ',', '.') }}
                                    </strong>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check mr-1"></i> Ya, Proses Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
