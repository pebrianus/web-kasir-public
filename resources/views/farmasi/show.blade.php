@extends('layouts.main')

@section('title', 'Detail Tagihan Farmasi')

@section('content')

    @php
        // ==============================
        // DATA DUMMY DETAIL TAGIHAN
        // ==============================
        $tagihan = (object) [
            'NOMOR' => 'TRX000001',
            'NAMA' => 'Budi Santoso',
            'TANGGAL' => '2026-02-18 10:15:00',
            'STATUS' => 'Belum Lunas',
            'DETAIL' => [
                (object) [
                    'nama_obat' => 'Paracetamol 60ML',
                    'qty' => 1,
                    'harga' => 25000,
                ],
                (object) [
                    'nama_obat' => 'Amoxicillin 500mg',
                    'qty' => 10,
                    'harga' => 6000,
                ],
            ],
        ];

        $total = 0;
    @endphp

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            Detail Tagihan Farmasi
        </h1>

        <a href="{{ route('farmasi.index') }}" class="btn btn-danger btn-sm">
            Kembali
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
                            <p><strong>Nama Pasien:</strong> {{ $tagihan->NAMA }}</p>
                            <p><strong>Nama Dokter:</strong> Pebri Dokter IGD</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Tanggal:</strong>
                                {{ \Carbon\Carbon::parse($tagihan->TANGGAL)->format('d-m-Y H:i') }}
                            </p>
                            <p>
                                <strong>Status:</strong>
                                <span class="badge badge-{{ $tagihan->STATUS == 'Belum Lunas' ? 'warning' : 'success' }}">
                                    {{ $tagihan->STATUS }}
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
                                @foreach($tagihan->DETAIL as $item)
                                    @php
                                        $subtotal = $item->qty * $item->harga;
                                        $total += $subtotal;
                                    @endphp
                                    <tr>
                                        <td>{{ $item->nama_obat }}</td>
                                        <td class="text-center">{{ $item->qty }}</td>
                                        <td class="text-right">
                                            Rp {{ number_format($item->harga, 0, ',', '.') }}
                                        </td>
                                        <td class="text-right">
                                            Rp {{ number_format($subtotal, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
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

                        <div class="card-body text-right">
                            @if($tagihan->STATUS == 'Belum Lunas')
                                <button class="btn btn-success">
                                    Proses Pembayaran
                                </button>
                            @else
                                <button class="btn btn-secondary">
                                    Cetak Struk
                                </button>
                            @endif

                        </div>
                    </div>
                </div>
            </div>



        </div>
    </div>

@endsection
