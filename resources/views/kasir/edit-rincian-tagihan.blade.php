@extends('layouts.main')
@section('title', 'Edit Rincian Tagihan: ' . $head->simgos_tagihan_id)

@section('content')
    <h1 class="h3 mb-4 text-gray-800">Edit Rincian Tagihan</h1>

    {{-- INFO TAGIHAN --}}
    <div class="card shadow mb-4">
        <div class="card-body py-2">
            <div class="row">
                <div class="col-md-4">
                    <small class="text-muted d-block">No. Tagihan</small>
                    <strong>{{ $head->simgos_tagihan_id }}</strong>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Pasien</small>
                    <strong>{{ $head->nama_pasien ?? '-' }}</strong>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Total Tagihan</small>
                    <strong id="total-tagihan-header">
                        {{ number_format($detail->sum('subtotal'), 2, ',', '.') }}
                    </strong>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('kasir.tagihan.rincian.update', ['id' => $head->id]) }}" method="POST" id="form-edit-rincian">
        @csrf
        @method('PUT')
        <input type="hidden" name="jenis_kasir" value="{{ request('jenis_kasir') }}">

        <div class="card shadow">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <span><i class="fas fa-edit mr-1"></i> Rincian Item Tagihan</span>
                <small>Edit qty dan harga satuan tiap item</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0" id="tabel-rincian">
                        <thead class="thead-light">
                            <tr>
                                <th class="text-center" width="4%">#</th>
                                <th>Item Tagihan</th>
                                <th class="text-center" width="12%">Qty</th>
                                <th class="text-center" width="20%">Harga Satuan</th>
                                <th class="text-center" width="20%">Subtotal</th>
                                <th class="text-center" width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($detail as $index => $item)
                                <tr id="row-{{ $item->id }}">
                                    <td class="text-center align-middle text-muted">{{ $index + 1 }}</td>
                                    <td class="align-middle">
                                        {{ $item->deskripsi_item }}
                                        <input type="hidden" name="items[{{ $item->id }}][id]" value="{{ $item->id }}">

                                        {{-- Tampilkan info asuransi/pasien jika sudah dibagi --}}
                                        @if ($item->nominal_ditanggung_asuransi > 0 || $item->nominal_ditanggung_pasien > 0)
                                            <small class="d-block text-muted mt-1">
                                                <span class="badge badge-info badge-sm">
                                                    Asuransi: {{ number_format($item->nominal_ditanggung_asuransi, 2, ',', '.') }}
                                                </span>
                                                <span class="badge badge-success badge-sm ml-1">
                                                    Pasien: {{ number_format($item->nominal_ditanggung_pasien, 2, ',', '.') }}
                                                </span>
                                            </small>
                                        @endif
                                    </td>

                                    {{-- QTY --}}
                                    <td class="align-middle">
                                        <input type="number" step="any" min="0"
                                            class="form-control form-control-sm text-center input-qty"
                                            name="items[{{ $item->id }}][qty]"
                                            value="{{ $item->qty }}"
                                            data-id="{{ $item->id }}"
                                            data-original="{{ $item->qty }}">
                                    </td>

                                    {{-- HARGA SATUAN --}}
                                    <td class="align-middle">
                                        <div class="input-group input-group-sm">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Rp</span>
                                            </div>
                                            <input type="number" step="any" min="0"
                                                class="form-control form-control-sm input-harga"
                                                name="items[{{ $item->id }}][harga_satuan]"
                                                value="{{ $item->harga_satuan }}"
                                                data-id="{{ $item->id }}"
                                                data-original="{{ $item->harga_satuan }}">
                                        </div>
                                    </td>

                                    {{-- SUBTOTAL (READ-ONLY) --}}
                                    <td class="align-middle text-right">
                                        <span class="subtotal-display font-weight-bold"
                                            id="subtotal-{{ $item->id }}"
                                            data-id="{{ $item->id }}">
                                            {{ number_format($item->subtotal, 2, ',', '.') }}
                                        </span>
                                        <input type="hidden" class="subtotal-value"
                                            name="items[{{ $item->id }}][subtotal]"
                                            id="subtotal-hidden-{{ $item->id }}"
                                            value="{{ $item->subtotal }}">
                                    </td>

                                    {{-- AKSI --}}
                                    <td class="text-center align-middle">
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-reset-row"
                                            data-id="{{ $item->id }}"
                                            data-original-qty="{{ $item->qty }}"
                                            data-original-harga="{{ $item->harga_satuan }}"
                                            title="Reset ke nilai asal">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                        <tfoot class="thead-light">
                            <tr>
                                <td colspan="4" class="text-right font-weight-bold">Total Tagihan</td>
                                <td class="text-right font-weight-bold text-primary" id="grand-total">
                                    {{ number_format($detail->sum('subtotal'), 2, ',', '.') }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- RINGKASAN PERUBAHAN --}}
        <div class="card shadow mt-3" id="card-perubahan" style="display: none !important;">
            <div class="card-header bg-warning text-white">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                <strong>Ringkasan Perubahan</strong>
            </div>
            <div class="card-body" id="ringkasan-perubahan">
            </div>
        </div>

        {{-- TOMBOL AKSI --}}
        <div class="row mt-4">
            <div class="col-12 text-right">
                <a href="{{ route('kasir.tagihan.lokal', ['id' => $head->id, 'jenis_kasir' => request('jenis_kasir')]) }}"
                    class="btn btn-danger">
                    <i class="fas fa-times mr-1"></i> Batal
                </a>
                <button type="button" class="btn btn-warning" id="btn-reset-semua">
                    <i class="fas fa-undo mr-1"></i> Reset Semua
                </button>
                <button type="submit" class="btn btn-success" id="btn-simpan">
                    <i class="fas fa-save mr-1"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {

            // ============================================================
            // FORMAT ANGKA
            // ============================================================
            function formatRupiah(angka) {
                let num = parseFloat(angka);
                if (isNaN(num)) num = 0;
                return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ".")
                          .replace(/\.(\d{2})$/, ",$1");
            }

            // ============================================================
            // HITUNG ULANG SUBTOTAL SATU BARIS
            // ============================================================
            function hitungSubtotal(id) {
                let qty    = parseFloat($(`input.input-qty[data-id="${id}"]`).val()) || 0;
                let harga  = parseFloat($(`input.input-harga[data-id="${id}"]`).val()) || 0;
                let subtotal = qty * harga;

                $(`#subtotal-${id}`).text(formatRupiah(subtotal));
                $(`#subtotal-hidden-${id}`).val(subtotal.toFixed(2));

                hitungGrandTotal();
                cekPerubahan();
            }

            // ============================================================
            // HITUNG GRAND TOTAL
            // ============================================================
            function hitungGrandTotal() {
                let total = 0;
                $('.subtotal-value').each(function () {
                    total += parseFloat($(this).val()) || 0;
                });
                $('#grand-total').text(formatRupiah(total));
                $('#total-tagihan-header').text(formatRupiah(total));
            }

            // ============================================================
            // CEK PERUBAHAN — TAMPILKAN RINGKASAN
            // ============================================================
            function cekPerubahan() {
                let perubahanList = [];

                $('.input-qty').each(function () {
                    let id          = $(this).data('id');
                    let qtyBaru     = parseFloat($(this).val()) || 0;
                    let qtyAsli     = parseFloat($(this).data('original')) || 0;
                    let hargaBaru   = parseFloat($(`input.input-harga[data-id="${id}"]`).val()) || 0;
                    let hargaAsli   = parseFloat($(`input.input-harga[data-id="${id}"]`).data('original')) || 0;

                    if (Math.abs(qtyBaru - qtyAsli) > 0.001 || Math.abs(hargaBaru - hargaAsli) > 0.001) {
                        let nama = $(`#row-${id} td:nth-child(2)`).contents().filter(function () {
                            return this.nodeType === 3;
                        }).text().trim();

                        perubahanList.push({
                            nama: nama,
                            qtyAsli: qtyAsli,
                            qtyBaru: qtyBaru,
                            hargaAsli: hargaAsli,
                            hargaBaru: hargaBaru
                        });
                    }
                });

                if (perubahanList.length > 0) {
                    let html = '<ul class="list-unstyled mb-0">';
                    perubahanList.forEach(function (item) {
                        html += `<li class="mb-2">
                            <strong>${item.nama}</strong><br>
                            <span class="text-muted">
                                Qty: <span class="text-danger">${item.qtyAsli}</span>
                                &rarr; <span class="text-success">${item.qtyBaru}</span>
                                &nbsp;|&nbsp;
                                Harga: <span class="text-danger">Rp ${formatRupiah(item.hargaAsli)}</span>
                                &rarr; <span class="text-success">Rp ${formatRupiah(item.hargaBaru)}</span>
                            </span>
                        </li>`;
                    });
                    html += '</ul>';
                    $('#ringkasan-perubahan').html(html);
                    $('#card-perubahan').show();
                } else {
                    $('#card-perubahan').hide();
                }
            }

            // ============================================================
            // EVENT: PERUBAHAN QTY / HARGA
            // ============================================================
            $(document).on('input', '.input-qty, .input-harga', function () {
                let id = $(this).data('id');
                hitungSubtotal(id);

                // Highlight baris yang berubah
                let qtyBaru   = parseFloat($(`input.input-qty[data-id="${id}"]`).val()) || 0;
                let qtyAsli   = parseFloat($(`input.input-qty[data-id="${id}"]`).data('original')) || 0;
                let hargaBaru = parseFloat($(`input.input-harga[data-id="${id}"]`).val()) || 0;
                let hargaAsli = parseFloat($(`input.input-harga[data-id="${id}"]`).data('original')) || 0;

                if (Math.abs(qtyBaru - qtyAsli) > 0.001 || Math.abs(hargaBaru - hargaAsli) > 0.001) {
                    $(`#row-${id}`).addClass('table-warning');
                } else {
                    $(`#row-${id}`).removeClass('table-warning');
                }
            });

            // ============================================================
            // TOMBOL RESET BARIS
            // ============================================================
            $(document).on('click', '.btn-reset-row', function () {
                let id         = $(this).data('id');
                let originalQty   = $(this).data('original-qty');
                let originalHarga = $(this).data('original-harga');

                $(`input.input-qty[data-id="${id}"]`).val(originalQty);
                $(`input.input-harga[data-id="${id}"]`).val(originalHarga);
                $(`#row-${id}`).removeClass('table-warning');
                hitungSubtotal(id);
            });

            // ============================================================
            // TOMBOL RESET SEMUA
            // ============================================================
            $('#btn-reset-semua').on('click', function () {
                if (!confirm('Reset semua perubahan ke nilai asal?')) return;

                $('.btn-reset-row').each(function () {
                    let id         = $(this).data('id');
                    let originalQty   = $(this).data('original-qty');
                    let originalHarga = $(this).data('original-harga');

                    $(`input.input-qty[data-id="${id}"]`).val(originalQty);
                    $(`input.input-harga[data-id="${id}"]`).val(originalHarga);
                    $(`#row-${id}`).removeClass('table-warning');
                    hitungSubtotal(id);
                });
            });

            // ============================================================
            // KONFIRMASI SEBELUM SUBMIT
            // ============================================================
            $('#form-edit-rincian').on('submit', function (e) {
                let adaPerubahan = false;

                $('.input-qty').each(function () {
                    let id       = $(this).data('id');
                    let qtyBaru  = parseFloat($(this).val()) || 0;
                    let qtyAsli  = parseFloat($(this).data('original')) || 0;
                    let hargaBaru = parseFloat($(`input.input-harga[data-id="${id}"]`).val()) || 0;
                    let hargaAsli = parseFloat($(`input.input-harga[data-id="${id}"]`).data('original')) || 0;

                    if (Math.abs(qtyBaru - qtyAsli) > 0.001 || Math.abs(hargaBaru - hargaAsli) > 0.001) {
                        adaPerubahan = true;
                    }
                });

                if (!adaPerubahan) {
                    e.preventDefault();
                    alert('Tidak ada perubahan yang perlu disimpan.');
                    return;
                }

                if (!confirm('Anda yakin ingin menyimpan perubahan rincian tagihan ini?')) {
                    e.preventDefault();
                }
            });

        });
    </script>
@endpush
