@extends('layouts.main')

@section('title', 'Kasir Farmasi')

@section('content')

    @php
        // ==============================
        // DATA DUMMY TAGIHAN FARMASI
        // ==============================
        $pasienList = collect([
            (object) [
                'NOMOR' => 'TRX000001',
                'NAMA' => 'Budi Santoso',
                'OBAT' => [
                    ['nama' => 'Paracetamol 60ML', 'qty' => 1],
                    ['nama' => 'Amoxicillin 500mg', 'qty' => 10],
                ],
                'TANGGAL' => '2026-02-18 10:15:00',
                'HARGA' => 85000,
            ],
            (object) [
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

        $statusFilter = 'proses'; // Default ke 'proses'
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
                    <div class="d-flex justify-content-between align-items-center">

                        <h6 class="m-0 font-weight-bold text-primary">
                            Daftar Tagihan Farmasi
                        </h6>

                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                Filter:
                                <strong>
                                    {{ $statusFilter == 'proses' ? 'Belum Selesai' : 'Selesai' }}
                                </strong>
                            </button>

                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item {{ $statusFilter == 'proses' ? 'active' : '' }}" href="#">
                                    Belum Selesai
                                </a>

                                <a class="dropdown-item {{ $statusFilter == 'selesai' ? 'active' : '' }}" href="#">
                                    Selesai
                                </a>
                            </div>
                        </div>

                    </div>
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
                                            <a href="{{ route('farmasi.show', $item->NOMOR) }}" class="btn btn-sm btn-primary">
                                                Buka
                                            </a>
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
