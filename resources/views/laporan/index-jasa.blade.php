@extends('layouts.main') {{-- <-- BERUBAH KE INDUK LAPORAN --}} @section('title', 'Laporan Penerimaan Kasir') {{--
    Konten ini akan dimasukkan ke @yield('laporan_content') --}} @section('content') @php
            // $namaDokter = count($data) ? collect($data)->first()->PETUGASMEDIS : null;

    $no = 1;
    $grandTotal = 0;

        @endphp <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Laporan Jasa Radiologi
            </h6>
        </div>
        <div class="card-body">

            {{-- Form Filter Tanggal --}}
            <form method="POST" action="{{ route('laporan.jasa.filter') }}">
                @csrf
                <div class="row align-items-end">

                    {{-- Tanggal Dari --}}
                    <div class="col-md-3">
                        <label class="small">Tanggal Dari</label>
                        <input type="date" class="form-control" name="tanggal_dari" value="{{ session('laporan_jasa_filter.tanggal_dari') }}">
                    </div>

                    {{-- Tanggal Sampai --}}
                    <div class="col-md-3">
                        <label class="small">Tanggal Sampai</label>
                        <input type="date" class="form-control" name="tanggal_sampai"
                            value="{{ session('laporan_jasa_filter.tanggal_sampai') }}">
                    </div>

                    {{-- Asuransi --}}
<div class="col-md-3">
    <label class="small">Asuransi</label>

    <select class="form-control" name="asuransi">
        <option value="Semua"
            {{ session('laporan_jasa_filter.asuransi', 'Semua') == 'Semua' ? 'selected' : '' }}>
            -- Semua Asuransi --
        </option>

        @foreach ($asuransiList as $asuransi)
            <option
                value="{{ $asuransi->DESKRIPSI }}"
                {{ session('laporan_jasa_filter.asuransi') == $asuransi->DESKRIPSI ? 'selected' : '' }}>
                {{ $asuransi->DESKRIPSI }}
            </option>
        @endforeach
    </select>
</div>


                    {{-- Dokter / Perawat --}}
<div class="col-md-3">


<select name="petugas" id="petugas" class="form-control">
    <option value="0">-- Semua Petugas --</option>

    @foreach ($petugasList as $petugas)
        <option
            value="{{ $petugas->ID_PETUGAS }}"
            data-jenis="{{ $petugas->JENIS }}"
            {{ session('laporan_jasa_filter.petugas') == $petugas->ID_PETUGAS ? 'selected' : '' }}
        >
            {{ $petugas->nama_petugas }}
        </option>
    @endforeach
</select>

<input type="hidden"
       name="jenis_petugas"
       id="jenis_petugas"
       value="{{ session('laporan_jasa_filter.jenis_petugas') }}">

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


@forelse ($data as $row)
    {{-- BARIS PASIEN --}}
    <tr class="bg-light">
        <td class="text-center">{{ $no++ }}</td>
        <td>{{ $row['no_rm'] }}</td>
        <td>{{ $row['nama_pasien'] }}</td>
        <td class="text-center">-</td>
        <td>-</td>
        <td class="text-right font-weight-bold">
            {{ number_format($row['total_fee'], 0, ',', '.') }}
        </td>
    </tr>

    {{-- DETAIL TINDAKAN --}}
    @foreach ($row['tindakan'] as $tdk)
        <tr>
            <td colspan="3" class="pl-4">
                • {{ $tdk['nama_tindakan'] }}
                <br>
                <small class="text-muted">
                    {{ \Carbon\Carbon::parse($tdk['tanggal'])->format('d-m-Y H:i') }}
                </small>
            </td>
            <td colspan="2">
                @forelse ($tdk['petugas'] as $p)
                    <div>
                        {{ $p['nama'] }}
                        <small class="text-muted">
                            ({{ $p['jenis'] == 1 ? 'Dokter' : 'Perawat' }})
                        </small>
                    </div>
                @empty
                    <em>-</em>
                @endforelse
            </td>
            <td class="text-right">
                {{ number_format($tdk['fee_petugas'], 0, ',', '.') }}
            </td>
        </tr>
    @endforeach

    @php
        $grandTotal += $row['total_fee'];
    @endphp

@empty
    <tr>
        <td colspan="6" class="text-center">
            Data tidak ditemukan
        </td>
    </tr>
@endforelse
</tbody>


                    {{-- GRAND TOTAL --}}
                    @if (count($data) > 0)
                        <tfoot>
                            <tr class="bg-light">
                                <td colspan="5" class="text-right font-weight-bold">
                                    TOTAL JASA BERSIH
                                </td>
                                <td class="text-right font-weight-bold">
                                    {{ number_format($grandTotal, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif



                </table>
            </div>

        </div>
        </div>

        <script>
            function setJenisPetugas() {
                let select = document.getElementById('petugas');
                let selected = select.options[select.selectedIndex];
                document.getElementById('jenis_petugas').value = selected.dataset.jenis || '';
            }

            // saat dropdown berubah
            document.getElementById('petugas').addEventListener('change', setJenisPetugas);

            // saat halaman pertama kali load
            document.addEventListener('DOMContentLoaded', setJenisPetugas);
        </script>



    @endsection
