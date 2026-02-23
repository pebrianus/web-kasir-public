@extends('layouts.main')

@section('title', 'Detail Tagihan Farmasi')

@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            Detail Tagihan Farmasi
        </h1>

        <a href="{{ route('farmasi.index') }}" class="btn btn-danger btn-sm">
            <i class="fas fa-times"></i>
        </a>
    </div>

    <div class="row">
        <div class="col-12">

            {{-- CARD INFORMASI TAGIHAN --}}
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>No. Transaksi:</strong> {{ $tagihan->NOMOR }}</p>
                            <p><strong>Nama Pasien:</strong> {{ $tagihan->PENGUNJUNG }}</p>
                            <p>
                                <strong>Nama Dokter:</strong>
                                {{ !empty($tagihan->DOKTER) ? $tagihan->DOKTER : '-' }}
                            </p>
                        </div>

                        <div class="col-md-6">
                            <p>
                                <strong>Tanggal:</strong>
                                {{ \Carbon\Carbon::parse($tagihan->TANGGAL)->format('d-m-Y H:i') }}
                            </p>

                            <p>
                                <strong>Status:</strong>
                                <span class="badge badge-warning">
                                    Belum Lunas
                                </span>
                                <span class="badge badge-success">
                                    Lunas
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CARD RINCIAN OBAT --}}
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Rincian Obat
                    </h6>
                </div>

                <div class="card-body">
                    <div class="row">

                        {{-- KOLOM TABEL --}}
                        <div class="col-md-9">
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
                                        @php $total = 0; @endphp

                                        @forelse($tagihan->OBAT as $item)
                                            @php
                                                $qty = (float) $item->JUMLAH;
                                                $harga = (float) $item->HARGA_JUAL_BARANG;
                                                $subtotal = $qty * $harga;
                                                $total += $subtotal;
                                            @endphp

                                            <tr>
                                                <td>{{ $item->NAMA_BARANG }}</td>
                                                <td class="text-center">
                                                    {{ rtrim(rtrim($item->JUMLAH, '0'), '.') }}
                                                </td>
                                                <td class="text-right">
                                                    Rp {{ number_format($harga, 0, ',', '.') }}
                                                </td>
                                                <td class="text-right">
                                                    Rp {{ number_format($subtotal, 0, ',', '.') }}
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
                                                Rp {{ number_format($total, 0, ',', '.') }}
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        {{-- KOLOM TOMBOL --}}
                        <div class="col-md-3 d-flex flex-column align-items-end">

                            <button class="btn btn-success mb-2">
                                <i class="fas fa-cash-register mr-1"></i>
                                Proses Pembayaran
                            </button>

                            <a href="{{ route('farmasi.cetakKuitansi', $tagihan->NOMOR) }}" class="btn btn-primary"
                                target="_blank">
                                <i class="fas fa-print mr-1"></i>
                                Cetak
                            </a>

                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection
