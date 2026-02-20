@extends('layouts.main')

@section('title', 'Kasir Farmasi')

@section('content')

    @php


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
                                @forelse ($data as $item)

                                    @php
                                        $totalHarga = 0;
                                    @endphp

                                    <tr>
                                        <td>{{ $item->NOMOR }}</td>
                                        <td>{{ $item->PENGUNJUNG }}</td>

                                        {{-- Kolom Obat --}}
                                        <td>
                                            @foreach($item->OBAT as $obat)

                                                @php
                                                    $subtotal = (float) $obat->JUMLAH * (float) $obat->HARGA_JUAL_BARANG;
                                                    $totalHarga += $subtotal;
                                                @endphp

                                                <div>
                                                    {{ $obat->NAMA_BARANG }}
                                                    ({{ rtrim(rtrim($obat->JUMLAH, '0'), '.') }})
                                                </div>
                                            @endforeach
                                        </td>

                                        <td>
                                            {{ \Carbon\Carbon::parse($item->TANGGAL)->format('d-m-Y H:i') }}
                                        </td>

                                        <td>
                                            Rp {{ number_format($totalHarga, 0, ',', '.') }}
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
                        <div class="mt-3">
                            {{ $data->links() }}
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

@endsection
