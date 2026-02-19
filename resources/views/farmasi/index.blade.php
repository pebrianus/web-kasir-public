@extends('layouts.main')

@section('title', 'Kasir Farmasi')

@section('content')

    @php
        // ==============================
        // DATA DUMMY TAGIHAN FARMASI
        // ==============================
        $pasienList = collect([
            (object)[
                'NOMOR' => 'TRX000001',
                'NAMA' => 'Budi Santoso',
                'OBAT' => [
                    ['nama' => 'Paracetamol 60ML', 'qty' => 1],
                    ['nama' => 'Amoxicillin 500mg', 'qty' => 10],
                ],
                'TANGGAL' => '2026-02-18 10:15:00',
                'HARGA' => 85000,
            ],
            (object)[
                'NOMOR' => 'TRX000002',
                'NAMA' => 'Siti Aminah',
                'OBAT' => [
                    ['nama' => 'OBH Combi', 'qty' => 2],
                    ['nama' => 'Vitamin C 1000mg', 'qty' => 5],
                ],
                'TANGGAL' => '2026-02-19 09:30:00',
                'HARGA' => 125000,
            ],
        ]);
    @endphp

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            Kasir Farmasi
        </h1>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">

                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar Tagihan Farmasi</h6>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Nomor</th>
                                    <th>Nama</th>
                                    <th>Obat</th>
                                    <th>Tanggal</th>
                                    <th>Harga</th>
                                    <th width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($pasienList as $item)
                                    <tr>
                                        <td>{{ $item->NOMOR }}</td>
                                        <td>{{ $item->NAMA }}</td>

                                        {{-- Kolom Obat --}}
                                        <td>
                                            @foreach($item->OBAT as $obat)
                                                <div>
                                                    {{ $obat['nama'] }} - {{ $obat['qty'] }}
                                                </div>
                                            @endforeach
                                        </td>

                                        <td>
                                            {{ \Carbon\Carbon::parse($item->TANGGAL)->format('d-m-Y H:i') }}
                                        </td>

                                        <td>
                                            Rp {{ number_format($item->HARGA, 0, ',', '.') }}
                                        </td>

                                        <td>
                                            <button class="btn btn-sm btn-primary">
                                                Buka
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            Data tidak ditemukan
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

@endsection
