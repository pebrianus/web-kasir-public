@extends('layouts.main') {{-- <-- BERUBAH KE INDUK LAPORAN --}} @section('title', 'Laporan Penerimaan Kasir') {{--
    Konten ini akan dimasukkan ke @yield('laporan_content') --}}


@section('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Samakan tinggi dan border Select2 dengan form-control Bootstrap */
        .select2-container .select2-selection--single {
            height: calc(1.5em + .75rem + 2px) !important;
            border: 1px solid #ced4da !important;
            border-radius: .25rem !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: calc(1.5em + .75rem + 2px) !important;
            color: #495057;
            padding-left: 10px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(1.5em + .75rem + 2px) !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #6c757d;
        }
    </style>
@endsection
@section('content') @php
    // $namaDokter = count($data) ? collect($data)->first()->PETUGASMEDIS : null;

    $no = 1;
    $grandTotal = 0;

@endphp <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Laporan Jasa Dokter
            </h6>
        </div>
        <div class="card-body">

            {{-- Form Filter Tanggal --}}
            <form method="POST" action="{{ route('laporan.jasa.dokter.filter') }}">
                @csrf
                <div class="row align-items-end">

                    {{-- Tanggal Dari --}}
                    <div class="col-md-3">
                        <label class="small">Tanggal Dari</label>
                        <input type="date" class="form-control" name="tanggal_dari"
                            value="{{ session('laporan_jasa_dokter_filter.tanggal_dari') }}">
                    </div>

                    {{-- Tanggal Sampai --}}
                    <div class="col-md-3">
                        <label class="small">Tanggal Sampai</label>
                        <input type="date" class="form-control" name="tanggal_sampai"
                            value="{{ session('laporan_jasa_dokter_filter.tanggal_sampai') }}">
                    </div>

                    {{-- Asuransi --}}
                    <div class="col-md-3">
                        <label class="small">Asuransi</label>

                        <select class="form-control" name="asuransi">
                            <option value="Semua"
                                {{ session('laporan_jasa_dokter_filter.asuransi', 'Semua') == 'Semua' ? 'selected' : '' }}>
                                -- Semua Asuransi --
                            </option>

                            @foreach ($asuransiList as $asuransi)
                                <option value="{{ $asuransi->DESKRIPSI }}"
                                    {{ session('laporan_jasa_dokter_filter.asuransi') == $asuransi->DESKRIPSI ? 'selected' : '' }}>
                                    {{ $asuransi->DESKRIPSI }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    {{-- Dokter / Perawat --}}
                    <div class="col-md-3">

                        <label class="small">Dokter</label>

                        <select name="dokter" id="dokter" class="form-control">
                            <option value="0" data-jenis="">-- Semua Dokter --</option>

                            @foreach ($dokterList as $dokter)
                                <option value="{{ $dokter->ID_DOKTER }}" data-jenis="{{ $dokter->JENIS }}"
                                    {{ session('laporan_jasa_dokter_filter.dokter') == $dokter->ID_DOKTER ? 'selected' : '' }}>
                                    {{ $dokter->nama_dokter }}
                                </option>
                            @endforeach
                        </select>



                        <input type="hidden" name="jenis_dokter" id="jenis_dokter"
                            value="{{ session('laporan_jasa_dokter_filter.jenis_dokter') }}">

                    </div>



                    {{-- Tombol --}}
                    <div class="col-md-12 mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            {{-- KIRI: Cari & Reset --}}
                            <div>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-search fa-sm me-1"></i> Cari
                                </button>

                                {{-- <a href="{{ route('laporan.jasa.cetak') }}" class="btn btn-secondary btn-sm ms-1">
                                    <i class="fas fa-undo fa-sm me-1"></i> Reset
                                </a> --}}
                            </div>

                            {{-- KANAN: Cetak --}}
                            <div>
                                <a href="{{ route('laporan.jasa.dokter.cetak') }}" target="_blank"
                                    class="btn btn-success btn-sm">
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
                            <th style="width:15%">Biaya</th>
                        </tr>
                    </thead>
                    <tbody>


                        @forelse ($data as $row)
                            {{-- BARIS PASIEN --}}
                            <tr class="bg-light">
                                <td class="text-center">{{ $no++ }}</td>
                                <td>{{ $row['no_rm'] }}</td>
                                <td>{{ $row['nama_pasien'] }}</td>
                                <td class="text-center">
                                    {{ \Carbon\Carbon::parse($row['tanggal_tagihan'])->format('d-m-Y') }}</td>
                                <td>{{ $row['nama_asuransi'] }}</td>
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
                                                    ({{ [1 => 'Dokter', 2 => 'Anastesi', 3 => 'Paramedis'][$p['jenis']] ?? 'Lainnya' }})
                                                    {{-- 👇 TAMPILAN FEE INDIVIDU DENGAN KONDISI 👇 --}}
                                                    @if($p['fee'] > 0)
                                                        - <span class="font-weight-bold">Rp {{ number_format($p['fee'], 0, ',', '.') }}</span>
                                                    @endif
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
                                        TOTAL BIAYA
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

    @endsection
    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
        <script>
            $(document).ready(function() {
                $('#dokter').select2({
                    placeholder: '-- Semua Dokter --',
                    allowClear: true,
                    width: '100%',
                });

                // Saat dropdown berubah
                $('#dokter').on('change', function() {
                    let selected = $(this).find(':selected');
                    $('#jenis_dokter').val(selected.data('jenis') || '');
                });

                // Saat halaman pertama kali load
                let initialJenis = $('#dokter').find(':selected').data('jenis') || '';
                $('#jenis_dokter').val(initialJenis);
            });
        </script>
        {{-- <script>
            function setJenisDokter() {
                let select = document.getElementById('dokter');
                let selected = select.options[select.selectedIndex];
                document.getElementById('jenis_dokter').value = selected.dataset.jenis || '';
            }

            // saat dropdown berubah
            document.getElementById('dokter').addEventListener('change', setJenisDokter);

            // saat halaman pertama kali load
            document.addEventListener('DOMContentLoaded', setJenisDokter);
        </script> --}}
    @endpush
