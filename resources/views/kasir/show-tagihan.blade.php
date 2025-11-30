@extends('layouts.main')

@section('title', 'History Pasien - ' . $pasien->NAMA)

@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        {{-- Judul Halaman --}}
        <h1 class="h3 mb-0 text-gray-800">History Tagihan Pasien</h1>

        {{-- Tombol History (kita sembunyikan dulu) --}}
        <a href="#" class="btn btn-sm btn-info shadow-sm d-none">
            <i class="fas fa-history fa-sm"></i> Lihat Tagihan Final
        </a>
    </div>

    {{-- ==== CARD DATA PASIEN (SESUAI MOCKUP) ==== --}}
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                {{-- Kolom Kiri: Data Pasien --}}
                <div class="col-md-8">
                    <dl class="row">
                        <dt class="col-sm-3">Nama Pasien</dt>
                        <dd class="col-sm-9 font-weight-bold">{{ $pasien->NAMA }}</dd>

                        <dt class="col-sm-3">NORM</dt>
                        <dd class="col-sm-9">{{ $pasien->NORM }}</dd>

                        <dt class="col-sm-3">Tanggal Lahir</dt>
                        <dd class="col-sm-9">{{ \Carbon\Carbon::parse($pasien->TANGGAL_LAHIR)->format('d F Y') }}</dd>
                    </dl>
                </div>
                {{-- Kolom Kanan: Tombol History (d-none = tersembunyi) --}}
                <div class="col-md-4 text-right d-none">
                    <button class="btn btn-primary">History</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ==== DAFTAR TAGIHAN (SESUAI MOCKUP) ==== --}}
    <div class="card shadow mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Daftar Tagihan</h6>

                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" id="dropdownMenuButton"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Filter:
                        {{-- Tampilkan status filter saat ini --}}
                        <strong>{{ $statusFilter == 'proses' ? 'Belum Selesai' : 'Selesai' }}</strong>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                        {{-- Link ke "Belum Selesai" --}}
                        <a class="dropdown-item {{ $statusFilter == 'proses' ? 'active' : '' }}"
                            href="{{ route('kasir.pasien.tagihan', ['norm' => $pasien->NORM, 'jenis_kasir' => $jenis_kasir, 'status' => 'proses']) }}">
                            Belum Selesai
                        </a>
                        {{-- Link ke "Selesai" --}}
                        <a class="dropdown-item {{ $statusFilter == 'selesai' ? 'active' : '' }}"
                            href="{{ route('kasir.pasien.tagihan', ['norm' => $pasien->NORM, 'jenis_kasir' => $jenis_kasir, 'status' => 'selesai']) }}">
                            Selesai
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">

            {{-- ==== Loop Daftar Tagihan ==== --}}

            @forelse ($daftarTagihan as $tagihan)
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row align-items-center">
                            {{-- Kolom Kiri: Detail Tagihan --}}
                            <div class="col-md-8">
                                <h5 class="font-weight-bold text-primary">No. Tagihan: {{ $tagihan->no_tagihan }}</h5>
                                <ul class="list-unstyled">
                                    <li><strong>Ruangan:</strong> {{ $tagihan->nama_ruangan }}</li>
                                    <li><strong>DPJP:</strong> {{ $tagihan->nama_dokter }}</li>
                                    <li><strong>Penjamin:</strong> {{ $tagihan->nama_asuransi }}</li>
                                    <li><strong>Tanggal Tagihan:</strong>
                                        {{ \Carbon\Carbon::parse($tagihan->tgl_tagihan)->format('d F Y H:i') }}</li>
                                </ul>
                            </div>

                            {{-- Kolom Kanan: Total & Tombol Aksi --}}
                            <div class="col-md-4 text-right">
                                <small>Total Tagihan</small>
                                <h4 class="font-weight-bold">
                                    {{-- Format sebagai Rupiah --}}
                                    Rp {{ number_format($tagihan->total_tagihan, 2, ',', '.') }}
                                </h4>
                                {{-- Tombol ini sekarang adalah form POST --}}
                                {{-- Cek apakah ID tagihan SIMGOS ini ada di dalam array $processedTags --}}
                                @if ($processedTags->has($tagihan->no_tagihan))
                                    @php $tagihanLokal = $processedTags[$tagihan->no_tagihan]; @endphp

                                    {{-- 1. SUDAH ADA & LUNAS --}}
                                    @if ($tagihanLokal->status_kasir == 'lunas')
                                                    <a href="{{ route('kasir.tagihan.lokal', [
                                            'id' => $tagihanLokal->id,
                                            'jenis_kasir' => request('jenis_kasir'),
                                        ]) }}" class="btn btn-primary btn-icon-split mt-2">

                                                        <span class="icon text-white-50">
                                                            <i class="fas fa-eye"></i>
                                                        </span>
                                                        <span class="text">Lihat Rincian</span>
                                                    </a>
                                    @else
                                                    {{-- 2. SUDAH ADA & MASIH DRAFT --}}
                                                    <a href="{{ route('kasir.tagihan.lokal', [
                                            'id' => $tagihanLokal->id,
                                            'jenis_kasir' => request('jenis_kasir'),
                                        ]) }}" class="btn btn-warning btn-icon-split mt-2">

                                                        <span class="icon text-white-50">
                                                            <i class="fas fa-arrow-right"></i>
                                                        </span>
                                                        <span class="text">Lanjutkan Proses</span>
                                                    </a>
                                    @endif
                                @else
                                    {{-- 3. BELUM ADA: PROSES TAGIHAN --}}
                                    <form action="{{ route('kasir.proses-tagihan') }}" method="POST">
                                        @csrf

                                        {{-- Hidden fields --}}
                                        <input type="hidden" name="simgos_tagihan_id" value="{{ $tagihan->no_tagihan }}">
                                        <input type="hidden" name="simgos_norm" value="{{ $pasien->NORM }}">
                                        <input type="hidden" name="jenis_kasir" value="{{ request('jenis_kasir') }}">

                                        <input type="hidden" name="nama_pasien" value="{{ $pasien->NAMA }}">
                                        <input type="hidden" name="nama_ruangan" value="{{ $tagihan->nama_ruangan }}">
                                        <input type="hidden" name="nama_dokter" value="{{ $tagihan->nama_dokter }}">
                                        <input type="hidden" name="nama_asuransi" value="{{ $tagihan->nama_asuransi }}">
                                        <input type="hidden" name="simgos_tanggal_tagihan" value="{{ $tagihan->tgl_tagihan }}">
                                        <input type="hidden" name="total_asli_simgos" value="{{ $tagihan->total_tagihan }}">

                                        <button type="submit" class="btn btn-success btn-icon-split mt-2">
                                            <span class="icon text-white-50">
                                                <i class="fas fa-arrow-right"></i>
                                            </span>
                                            <span class="text">Proses Tagihan</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                {{-- Tampilan jika tidak ada tagihan --}}
                <div class="text-center">
                    <p>Tidak ada tagihan dengan status
                        <strong>"{{ $statusFilter == 'proses' ? 'Belum Selesai' : 'Selesai' }}"</strong>.
                    </p>
                </div>
            @endforelse
        </div>
    </div>

@endsection
