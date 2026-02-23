@extends('layouts.main')

@section('title', 'Kasir Farmasi')

@section('content')

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
                                <a class="dropdown-item {{ $statusFilter == 'proses' ? 'active' : '' }}"
                                    href="{{ route('farmasi.index', ['status' => 'proses']) }}">
                                    Belum Selesai
                                </a>

                                <a class="dropdown-item {{ $statusFilter == 'selesai' ? 'active' : '' }}"
                                    href="{{ route('farmasi.index', ['status' => 'selesai']) }}">
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
                                    <th>Tanggal</th>
                                    <th>Harga</th>
                                    <th width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data as $item)
                                    <tr>
                                        {{-- Variabel diseragamkan dengan field di database lokal --}}
                                        <td>{{ $item->simgos_penjualan_id }}</td>
                                        <td>{{ $item->nama_pengunjung }}</td>
                                        <td>
                                            {{ \Carbon\Carbon::parse($item->simgos_tanggal)->format('d-m-Y H:i') }}
                                        </td>
                                        <td>
                                            Rp {{ number_format($item->total_tagihan, 0, ',', '.') }}
                                        </td>
                                        <td>
                                            <a href="{{ route('farmasi.show', $item->simgos_penjualan_id) }}"
                                                class="btn btn-sm btn-primary">
                                                Buka
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">
                                            Data tidak ditemukan
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="mt-3">
                            {{ $data->appends(['status' => $statusFilter])->links() }}
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

@endsection
