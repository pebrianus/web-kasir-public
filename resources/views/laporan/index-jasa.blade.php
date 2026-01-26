@extends('layouts.main') {{-- <-- BERUBAH KE INDUK LAPORAN --}} @section('title', 'Laporan Penerimaan Kasir') {{--
    Konten ini akan dimasukkan ke @yield('laporan_content') --}} @section('content') @php


        @endphp <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Laporan Jasa Radiologi
            </h6>
        </div>
        <div class="card-body">

            {{-- Form Filter Tanggal --}}
            <form method="GET" action="{{ route('laporan.jasa.index') }}">

                <div class="row align-items-end">

                    {{-- Tanggal Dari --}}
                    <div class="col-md-3">
                        <label class="small">Tanggal Dari</label>
                        <input type="date" class="form-control" name="tanggal_dari" value="{{ request('tanggal_dari') }}">
                    </div>

                    {{-- Tanggal Sampai --}}
                    <div class="col-md-3">
                        <label class="small">Tanggal Sampai</label>
                        <input type="date" class="form-control" name="tanggal_sampai"
                            value="{{ request('tanggal_sampai') }}">
                    </div>

                    {{-- Asuransi --}}
                    <div class="col-md-3">
                        <label class="small">Asuransi</label>
                        <select class="form-control" name="asuransi">
                            <option value="0" {{ request('asuransi') == '0' ? 'selected' : '' }}>
                                -- Semua Asuransi --
                            </option>

                            @foreach ($asuransiList as $asuransi)
                                <option value="{{ $asuransi->ID }}" {{ request('asuransi') == $asuransi->ID ? 'selected' : '' }}>
                                    {{ $asuransi->DESKRIPSI }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Dokter / Perawat --}}
                    <div class="col-md-3">
                        <label class="small">Dokter / Perawat</label>
                        <select name="petugas" class="form-control">
                            <option value="0">-- Semua Petugas --</option>

                            @foreach ($petugasList as $petugas)
                                <option value="{{ $petugas->NIP }}" {{ request('petugas') == $petugas->NIP ? 'selected' : '' }}>
                                    {{ $petugas->nama_petugas }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tombol --}}
                    <div class="col-md-12 mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            {{-- KIRI: Cari & Reset --}}
                            <div>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-search fa-sm me-1"></i> Cari
                                </button>

                                <a href="{{ route('laporan.jasa.cetak') }}" class="btn btn-secondary btn-sm ms-1">
                                    <i class="fas fa-undo fa-sm me-1"></i> Reset
                                </a>
                            </div>

                            {{-- KANAN: Cetak --}}
                            <div>
                                <a href="{{ route('laporan.jasa.cetak') }}" target="blank" class="btn btn-success btn-sm">
                                    <i class="fas fa-print fa-sm me-1"></i> Cetak
                                </a>
                            </div>
                        </div>
                    </div>


                </div>
            </form>

            <hr>

            {{-- Tabel Hasil --}}
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">

                    <thead class="text-center">
                        <tr>
                            <th style="width:5%">No</th>
                            <th style="width:15%">No. RM</th>
                            <th style="width:30%">Nama Pasien</th>
                            <th style="width:15%">Tgl Reg</th>
                            <th style="width:20%">Cara Bayar</th>
                            <th style="width:15%">Jasa Dokter</th>
                        </tr>
                    </thead>

                    <tbody>
                        {{-- Data Pasien --}}
                        <tr>
                            <td class="text-center">1</td>
                            <td>00-35-93-23</td>
                            <td>BUDIANSYAH</td>
                            <td class="text-center">29-12-2025</td>
                            <td>Tanpa Asuransi / 1</td>
                            <td class="text-right">120.000</td>
                        </tr>

                        {{-- Detail Tindakan --}}
                        <tr>
                            <td colspan="5">
                                Pemeriksaan Dokter IGD
                            </td>
                            <td class="text-right">
                                120.000
                            </td>
                        </tr>

                        {{-- Subtotal --}}
                        <tr>
                            <td colspan="5" class="text-right font-weight-bold">
                                Jumlah IGD
                            </td>
                            <td class="text-right font-weight-bold">
                                120.000
                            </td>
                        </tr>

                        {{-- Total Sebelum Diskon --}}
                        <tr>
                            <td colspan="5" class="text-right">
                                Total Jasa Sebelum Diskon PEBRI DOKTER IGD
                            </td>
                            <td class="text-right">
                                120.000
                            </td>
                        </tr>

                        {{-- Total Bersih --}}
                        <tr class="bg-light">
                            <td colspan="5" class="text-right font-weight-bold">
                                Total Jasa Bersih PEBRI DOKTER IGD
                            </td>
                            <td class="text-right font-weight-bold">
                                120.000
                            </td>
                        </tr>
                    </tbody>

                </table>
            </div>

        </div>
        </div>

    @endsection
