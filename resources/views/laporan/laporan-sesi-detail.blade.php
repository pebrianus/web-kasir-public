@extends('layouts.main') {{-- Kita pakai layout utama --}}
@section('title', 'Laporan Sesi ' . $sesi->nama_sesi)

@section('content')
    @php
        $jenisList = [
            1 => 'Rawat Jalan',
            2 => 'IGD',
            3 => 'Rawat Inap',
            4 => 'Laboratorium',
            5 => 'Radiologi',
        ];

        $jenis_kasir = request('jenis');
    @endphp
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Detail Laporan Sesi</h6>
            <a href="{{ route('laporan.sesi.cetak', ['id' => $sesi->id, 'jenis' => $jenis_kasir]) }}" target="_blank"
                class="btn btn-sm btn-primary">
                <i class="fas fa-print fa-sm"></i> Cetak Laporan (PDF)
            </a>

        </div>
        <div class="card-body">

            {{-- KOP LAPORAN (Sesuai Mockup) --}}
            <div class="text-center">
                <h5 class="font-weight-bold">LAPORAN PENERIMAAN PERKASIR</h5>
                <h6 class="font-weight-bold">RS SUAKA INSAN BANJARMASIN</h6>
                <p class="mb-0">TANGGAL: {{ $sesi->waktu_buka->format('d F Y') }}</p>
                <p>SHIFT: {{ $sesi->nama_sesi }} (Dibuka: {{ $sesi->waktu_buka->format('H:i') }} - Ditutup:
                    {{ $sesi->waktu_tutup ? $sesi->waktu_tutup->format('H:i') : '-' }})
                </p>
            </div>

            <hr>

            <div class="row mb-2">
                <div class="col-md-6">
                    KASIR: {{ $jenisList[$jenis_kasir] ?? 'Tidak diketahui' }}
                </div>
                <div class="col-md-6 text-right">
                    TANGGAL WAKTU: {{ now()->format('d-m-Y H:i:s') }}
                </div>
            </div>

            {{-- TABEL DATA (Sesuai Mockup) --}}
            <div class="table-responsive">
                <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                    <thead class="thead-dark">
                        <tr>
                            <th>No</th>
                            <th>No RM</th>
                            <th>Nama</th>
                            <th>No Tagihan</th>
                            <th class="text-right">Tunai (Pasien)</th>
                            <th class="text-right">Subsidi RS</th>
                            <th class="text-right">Piutang (Asuransi)</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($daftarTransaksi as $index => $tx)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $tx->norm }}</td>
                                <td>{{ $tx->nama }}</td>
                                <td>{{ $tx->no_tagihan }}</td>
                                <td class="text-right">{{ number_format($tx->tunai, 0, ',', '.') }}</td>
                                <td class="text-right">0</td>
                                <td class="text-right">{{ number_format($tx->piutang, 0, ',', '.') }}</td>
                                <td></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">Tidak ada transaksi pada sesi ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <td colspan="4" class="text-right">Total</td>
                            <td class="text-right">Rp {{ number_format($totals['total_tunai'], 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($totals['total_subsidi'], 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($totals['total_piutang'], 0, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>
    </div>
@endsection
