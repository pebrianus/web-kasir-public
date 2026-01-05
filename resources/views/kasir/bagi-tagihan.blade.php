@extends('layouts.main')
@section('title', 'Bagi Tagihan: ' . $head->simgos_tagihan_id)

@section('content')
    <h1 class="h3 mb-4 text-gray-800">Pisah Tagihan</h1>

    <form action="{{ route('kasir.tagihan.bagi.store', ['id' => $head->id]) }}" method="POST" id="form-bagi-tagihan">
        @csrf
        <input type="hidden" name="jenis_kasir" value="{{ request('jenis_kasir') }}">

        <div class="row">
            {{-- KOLOM KIRI: TAGIHAN ASURANSI (INPUT) --}}
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        Tagihan Asuransi (Bisa Diedit)
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Item Tagihan</th>
                                    <th width="35%">Tarif Ditanggung Asuransi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($detail as $item)
                                    <tr>
                                        <td>{{ $item->deskripsi_item }}
                                            <small class="d-block text-muted">
                                                {{-- (Tarif Asli: {{ number_format($item->subtotal, 0, ',', '.') }}) --}}
                                                (Tarif Asli: {{ number_format($item->subtotal, 2, ',', '.') }})

                                            </small>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="any"
                                                    class="form-control form-control-sm input-asuransi"
                                                    name="asuransi[{{ $item->id }}]"
                                                    value="{{ $item->nominal_ditanggung_asuransi }}"
                                                    data-subtotal="{{ $item->subtotal }}" data-id-rincian="{{ $item->id }}">

                                                <div class="input-group-append">
                                                    <!-- Tombol Copy -->
                                                    <button type="button" class="btn btn-info btn-copy"
                                                        data-id-rincian="{{ $item->id }}" title="Salin ke Asuransi">
                                                        <i class="fas fa-copy"></i>
                                                    </button>

                                                    <!-- Tombol Hapus -->
                                                    <button type="button" class="btn btn-danger btn-clear"
                                                        data-id-rincian="{{ $item->id }}" title="Set 0">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>

                                            </div>
                                        </td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="text-right mt-3">
                            <button type="button" class="btn btn-sm btn-info" id="btn-copy-all">
                                <i class="fas fa-copy"></i> Copy Semua ke Asuransi
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: TAGIHAN PASIEN (READ-ONLY) --}}
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        Tagihan Pasien (Otomatis)
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Item Tagihan</th>
                                    <th width="35%">Tarif Ditanggung Pasien</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($detail as $item)
                                    <tr>
                                        <td>{{ $item->deskripsi_item }}</td>
                                        <td class="text-right" id="pasien-{{ $item->id }}"> {{-- Beri ID unik --}}
                                            {{-- {{ number_format($item->nominal_ditanggung_pasien, 0, ',', '.') }} --}}
                                            {{ number_format($item->nominal_ditanggung_pasien, 2, ',', '.') }}

                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- TOMBOL AKSI --}}
        <div class="row mt-4">
            <div class="col-12 text-right">
                <a href="{{ route('kasir.tagihan.lokal', ['id' => $head->id, 'jenis_kasir' => request('jenis_kasir')]) }}"
                    class="btn btn-danger">Batal</a>
                <button type="button" class="btn btn-warning" id="btn-reset">Reset</button>
                <button type="submit" class="btn btn-success">Simpan</button>
            </div>
        </div>
    </form>
@endsection

{{-- TAMBAHKAN JAVASCRIPT DI BAWAH --}}
@push('scripts')
    <script>
        $(document).ready(function () {
            function formatRupiah(angka) {
                return angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            }

            $('.input-asuransi').on('input', function () {
                let inputAsuransi = $(this).val() ? parseFloat($(this).val()) : 0;
                let subtotal = parseFloat($(this).data('subtotal'));
                let idRincian = $(this).data('id-rincian');

                let sisaPasien = subtotal - inputAsuransi;
                if (sisaPasien < 0) {
                    sisaPasien = 0;
                    $(this).val(subtotal);
                }

                $('#pasien-' + idRincian).text(formatRupiah(sisaPasien));
            });

            // Reset semua
            $('#btn-reset').on('click', function (e) {
                e.preventDefault();
                if (confirm('Anda yakin ingin mereset pembagian tagihan?')) {
                    $('.input-asuransi').each(function () {
                        $(this).val(0).trigger('input');
                    });
                }
            });

            // Tombol COPY -> set asuransi = subtotal asli
            $('.btn-copy').on('click', function () {
                let id = $(this).data('id-rincian');
                let input = $(`input[data-id-rincian="${id}"]`);
                let subtotal = parseFloat(input.data('subtotal'));

                input.val(subtotal).trigger('input');
            });

            // Tombol CLEAR -> set asuransi jadi 0
            $('.btn-clear').on('click', function () {
                let id = $(this).data('id-rincian');
                let input = $(`input[data-id-rincian="${id}"]`);

                input.val(0).trigger('input');
            });

            // Tombol COPY ALL -> semua asuransi = subtotal
            $('#btn-copy-all').on('click', function () {
                if (!confirm('Salin semua tagihan ke Asuransi?')) return;

                $('.input-asuransi').each(function () {
                    let subtotal = parseFloat($(this).data('subtotal'));
                    $(this).val(subtotal).trigger('input');
                });
            });

        });
    </script>

@endpush
