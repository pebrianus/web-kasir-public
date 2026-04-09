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
                        <div class="d-flex align-items-center gap-2">
                            {{-- Search Bar --}}
                            <form method="GET" action="{{ route('farmasi.index') }}" class="mr-2">
                                <input type="hidden" name="status" value="{{ $statusFilter ?? 'proses' }}">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Cari Nama atau Nomor..."
                                        name="search" value="{{ request('search') }}">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search fa-sm"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                            {{-- Filter Dropdown --}}
                            <div class="dropdown">
                                <button class="btn btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown"
                                    aria-haspopup="true" aria-expanded="false">
                                    Filter:
                                    <strong>
                                        {{ ($statusFilter ?? 'proses') == 'proses' ? 'Belum Selesai' : 'Selesai' }}
                                    </strong>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item {{ ($statusFilter ?? 'proses') == 'proses' ? 'active' : '' }}"
                                        href="{{ route('farmasi.index', ['status' => 'proses', 'search' => request('search')]) }}">
                                        Belum Selesai
                                    </a>
                                    <a class="dropdown-item {{ ($statusFilter ?? '') == 'selesai' ? 'active' : '' }}"
                                        href="{{ route('farmasi.index', ['status' => 'selesai', 'search' => request('search')]) }}">
                                        Selesai
                                    </a>
                                </div>
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
                        @if ($data->hasPages() || $data->total() > 0)
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="text-muted small">
                                    Menampilkan {{ $data->firstItem() ?? 0 }} - {{ $data->lastItem() ?? 0 }} dari
                                    {{ $data->total() }} data
                                </div>
                                <div>
                                    {{ $data->appends(['status' => $statusFilter])->links() }}
                                </div>
                            </div>
                        @endif

                    </div>

                </div>
            </div>
        </div>
    </div>

@endsection
