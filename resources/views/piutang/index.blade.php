@extends('layouts.main')

@section('title', 'Piutang')

@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            Piutang
        </h1>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">

                <div class="card-header py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            Daftar Tagihan Piutang
                        </h6>

                        <div class="d-flex align-items-center gap-2">
                            {{-- Search Bar --}}
                            <form method="GET" action="{{ route('piutang.index') }}" class="mr-2">
                                <input type="hidden" name="status" value="{{ $statusFilter ?? 'belum' }}">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Cari Nama atau No RM..."
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
                                        {{ ($statusFilter ?? 'belum') == 'belum' ? 'Belum Lunas' : 'Lunas' }}
                                    </strong>
                                </button>

                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item {{ ($statusFilter ?? 'belum') == 'belum' ? 'active' : '' }}"
                                        href="{{ route('piutang.index', ['status' => 'belum', 'search' => request('search')]) }}">
                                        Belum Lunas
                                    </a>
                                    <a class="dropdown-item {{ ($statusFilter ?? '') == 'lunas' ? 'active' : '' }}"
                                        href="{{ route('piutang.index', ['status' => 'lunas', 'search' => request('search')]) }}">
                                        Lunas
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
                                    <th>Nama</th>
                                    <th>No RM</th>
                                    <th>Tanggal</th>
                                    <th>Total Biaya</th>
                                    <th>Piutang</th>
                                    <th>Nama Asuransi</th>
                                    <th width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>


                                @forelse ($data ?? $dummyData as $item)
                                    @php
                                        $nama = is_array($item) ? $item['nama'] : $item->nama;
                                        $no_rm = is_array($item) ? $item['no_rm'] : $item->no_rm;
                                        $tanggal = is_array($item) ? $item['tanggal'] : $item->tanggal;
                                        $total_biaya = is_array($item) ? $item['total_biaya'] : $item->total_biaya;
                                        $piutang = is_array($item) ? $item['piutang'] : $item->piutang;
                                        $nama_asuransi = is_array($item) ? $item['nama_asuransi'] : $item->nama_asuransi;
                                        $id = is_array($item) ? $item['id'] : $item->id;
                                    @endphp
                                    <tr>
                                        <td>{{ $nama }}</td>
                                        <td>{{ $no_rm }}</td>
                                        <td>{{ \Carbon\Carbon::parse($tanggal)->format('d-m-Y H:i') }}</td>
                                        <td>Rp {{ number_format($total_biaya, 0, ',', '.') }}</td>
                                        <td>Rp {{ number_format($piutang, 0, ',', '.') }}</td>
                                        <td>{{ $nama_asuransi }}</td>
                                        <td>
                                            <a href="{{ route('piutang.detail', $id) }}" class="btn btn-sm btn-primary">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            Data tidak ditemukan
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        {{-- Pagination (hanya tampil jika $data dikirim dari controller) --}}
                        @isset($data)
                            @if ($data->hasPages() || $data->total() > 0)
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="text-muted small">
                                        Menampilkan {{ $data->firstItem() ?? 0 }} - {{ $data->lastItem() ?? 0 }} dari
                                        {{ $data->total() }} data
                                    </div>
                                    <div>
                                        {{ $data->appends(['status' => $statusFilter ?? 'belum'])->links() }}
                                    </div>
                                </div>
                            @endif
                        @endisset

                    </div>
                </div>

            </div>
        </div>
    </div>

@endsection
