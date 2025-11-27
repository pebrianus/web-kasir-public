@extends('layouts.laporan') {{-- <-- BERUBAH KE INDUK LAPORAN --}}

@section('title', 'Laporan Penerimaan Kasir')

{{-- Konten ini akan dimasukkan ke @yield('laporan_content') --}}
@section('laporan_content')

    @php
        $jenis = [
            1 => 'Rawat Jalan',
            2 => 'IGD',
            3 => 'Rawat Inap',
        ];
    @endphp

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Laporan Penerimaan
                {{ $jenis[$jenis_kasir] ?? 'Nama belum ditambah' }}</h6>
        </div>
        <div class="card-body">

            {{-- Form Filter Tanggal --}}
            <form method="GET" action="{{ route('laporan.penerimaan.index') }}">
                <div class="row">
                    <div class="col-md-5">
                        <input type="date" class="form-control" name="tanggal" value="{{ $tanggalInput }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search fa-sm"></i> Cari
                        </button>
                    </div>
                </div>
            </form>

            <hr>

            {{-- Tabel Hasil --}}
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Jam Buka</th>
                            <th>Jam Tutup</th>
                            <th class="text-right">Total Penerimaan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($daftarSesi as $index => $sesi)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $sesi->waktu_buka->format('H:i:s') }}</td>
                                <td>{{ $sesi->waktu_tutup ? $sesi->waktu_tutup->format('H:i:s') : '-' }}</td>
                                <td class="text-right">Rp {{ number_format($sesi->total_penerimaan_sistem, 0, ',', '.') }}
                                </td>
                                <td>
                                    <a href="{{ route('laporan.sesi.show', ['id' => $sesi->id]) }}"
                                        class="btn btn-sm btn-info">
                                        Lihat Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">Belum ada sesi kasir yang ditutup pada tanggal ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Link Paginasi --}}
            <div class="d-flex justify-content-center">
                {{ $daftarSesi->appends(request()->query())->links() }}
            </div>

        </div>
    </div>

@endsection
