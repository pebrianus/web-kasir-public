@extends('layouts.main')

@section('title', 'Pencarian Pasien Rawat Jalan')

@section('content')






{{-- Backup Copas rawat-jalan --}}










<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Kasir Rawat Jalan - Pencarian Pasien</h1>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            {{-- KEMBALIKAN FORM PENCARIAN --}}
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Data Pasien</h6>
                
                <form method="GET" action="{{ route('pencarian.rawat-jalan') }}">
                    {{-- Simpan token jenis kasir --}}
                    <input type="hidden" name="jenis" value="{{ $jenis_kasir }}">
                    <div class="input-group">
                        <input type="text" class="form-control" 
                               placeholder="Cari NAMA atau NORM..." 
                               name="search" 
                               value="{{ request('search') }}">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search fa-sm"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    {{-- Hapus id="pasien-table" --}}
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>NORM</th>
                                <th>Nama Pasien</th>
                                <th>Tanggal Lahir</th>
                                <th>Alamat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- KEMBALIKAN LOOP @forelse --}}
                            @forelse ($pasienList as $pasien)
                                <tr>
                                    {{-- Perbaiki nomor urut untuk paginasi --}}
                                    <td>{{ $pasien->NORM }}</td>
                                    <td>{{ $pasien->NAMA }}</td>
                                    <td>{{ \Carbon\Carbon::parse($pasien->TANGGAL_LAHIR)->format('d-m-Y') }}</td>
                                    {{-- Potong alamat --}}
                                    <td>{{ \Illuminate\Support\Str::limit($pasien->ALAMAT, 60, '...') }}</td>
                                    <td>
                                        <a href="{{ route('kasir.pasien.tagihan', ['norm' => $pasien->NORM, 'jenis_kasir' => $jenis_kasir]) }}" class="btn btn-sm btn-primary">Buka</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">Data pasien tidak ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                {{-- KEMBALIKAN LINK PAGINASI --}}
                <div class="d-flex justify-content-center">
                    {{-- withQueryString() agar filter pencarian tetap ada saat ganti halaman --}}
                    {!! $pasienList->withQueryString()->links() !!}
                </div>

            </div>
        </div>
    </div>
</div>

@endsection

{{-- HAPUS @push('scripts') --}}