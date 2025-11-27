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
                                                    data-subtotal="{{ $item->subtotal }}"
                                                    data-id-rincian="{{ $item->id }}">

                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-info btn-copy"
                                                        data-id-rincian="{{ $item->id }}">
                                                        Copy
                                                    </button>
                                                </div>
                                            </div>
                                        </td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
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
        $(document).ready(function() {
            // Fungsi untuk memformat angka sebagai Rupiah
            function formatRupiah(angka) {
                return angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            }

            // Tangkap event 'input' (saat kasir mengetik) di kotak asuransi
            $('.input-asuransi').on('input', function() {
                let inputAsuransi = $(this).val() ? parseFloat($(this).val()) : 0;
                let subtotal = parseFloat($(this).data('subtotal'));
                let idRincian = $(this).data('id-rincian');

                // Hitung sisa
                let sisaPasien = subtotal - inputAsuransi;

                // Jika minus, jangan biarkan
                if (sisaPasien < 0) {
                    sisaPasien = 0;
                    $(this).val(subtotal); // Set input asuransi ke nilai max
                }

                // Update tabel pasien (kanan)
                $('#pasien-' + idRincian).text(formatRupiah(sisaPasien));
            });

            // Logika Tombol Reset
            $('#btn-reset').on('click', function(e) {
                e.preventDefault();
                if (confirm('Anda yakin ingin mereset pembagian tagihan?')) {
                    // Set semua input asuransi jadi 0
                    $('.input-asuransi').each(function() {
                        $(this).val(0);
                        // Picu event input untuk update tabel kanan
                        $(this).trigger('input');
                    });
                }
            });

        });

        // Tombol COPY -> isi input asuransi dengan nominal pasien
        $('.btn-copy').on('click', function() {
            let id = $(this).data('id-rincian');
            let subtotal = parseFloat($(`input[data-id-rincian="${id}"]`).data('subtotal'));

            // Ambil nilai asuransi yang sekarang
            let asuransi = parseFloat($(`input[data-id-rincian="${id}"]`).val()) || 0;

            // Hitung sisa (pasien)
            let sisaPasien = subtotal - asuransi;

            // Set input asuransi = sisa pasien (copy)
            $(`input[data-id-rincian="${id}"]`).val(sisaPasien).trigger('input');
        });
    </script>
@endpush
