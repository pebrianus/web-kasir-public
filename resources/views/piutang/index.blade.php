@extends('layouts.main')

@section('title', 'Piutang')

@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            Piutang
        </h1>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">

                <div class="card-header py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            Daftar Tagihan Piutang
                        </h6>

                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                Filter:
                                <strong>
                                    {{ ($statusFilter ?? 'belum') == 'belum' ? 'Belum Lunas' : 'Lunas' }}
                                </strong>
                            </button>

                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item {{ ($statusFilter ?? 'belum') == 'belum' ? 'active' : '' }}"
                                    href="{{ route('piutang.index', ['status' => 'belum']) }}">
                                    Belum Lunas
                                </a>
                                <a class="dropdown-item {{ ($statusFilter ?? '') == 'lunas' ? 'active' : '' }}"
                                    href="{{ route('piutang.index', ['status' => 'lunas']) }}">
                                    Lunas
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>No RM</th>
                                    <th>Tanggal</th>
                                    <th>Total Biaya</th>
                                    <th>Piutang</th>
                                    <th>Nama Asuransi</th>
                                    <th width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    // ── DATA DUMMY ──────────────────────────────────────────
                                    $dummyData = [
                                        [
                                            'nama' => 'Budi Santoso',
                                            'no_rm' => 'RM-001234',
                                            'tanggal' => '2025-06-01 08:30:00',
                                            'total_biaya' => 1500000,
                                            'piutang' => 750000,
                                            'nama_asuransi' => 'BPJS Kesehatan',
                                            'id' => 1,
                                        ],
                                        [
                                            'nama' => 'Siti Rahma',
                                            'no_rm' => 'RM-002567',
                                            'tanggal' => '2025-06-02 10:15:00',
                                            'total_biaya' => 3200000,
                                            'piutang' => 3200000,
                                            'nama_asuransi' => 'Prudential',
                                            'id' => 2,
                                        ],
                                        [
                                            'nama' => 'Ahmad Fauzi',
                                            'no_rm' => 'RM-003891',
                                            'tanggal' => '2025-06-03 14:00:00',
                                            'total_biaya' => 800000,
                                            'piutang' => 400000,
                                            'nama_asuransi' => 'Allianz',
                                            'id' => 3,
                                        ],
                                        [
                                            'nama' => 'Dewi Lestari',
                                            'no_rm' => 'RM-004122',
                                            'tanggal' => '2025-06-04 09:45:00',
                                            'total_biaya' => 5600000,
                                            'piutang' => 2800000,
                                            'nama_asuransi' => 'AXA Mandiri',
                                            'id' => 4,
                                        ],
                                        [
                                            'nama' => 'Rudi Hermawan',
                                            'no_rm' => 'RM-005678',
                                            'tanggal' => '2025-06-05 11:20:00',
                                            'total_biaya' => 920000,
                                            'piutang' => 920000,
                                            'nama_asuransi' => 'BPJS Kesehatan',
                                            'id' => 5,
                                        ],
                                    ];
                                    // ────────────────────────────────────────────────────────
                                @endphp

                                @forelse ($data ?? $dummyData as $item)
                                    @php
                                        $nama = is_array($item) ? $item['nama'] : $item->nama;
                                        $no_rm = is_array($item) ? $item['no_rm'] : $item->no_rm;
                                        $tanggal = is_array($item) ? $item['tanggal'] : $item->tanggal;
                                        $total_biaya = is_array($item) ? $item['total_biaya'] : $item->total_biaya;
                                        $piutang = is_array($item) ? $item['piutang'] : $item->piutang;
                                        $nama_asuransi = is_array($item) ? $item['nama_asuransi'] : $item->nama_asuransi;
                                        $id = is_array($item) ? $item['id'] : $item->id;
                                    @endphp
                                    <tr>
                                        <td>{{ $nama }}</td>
                                        <td>{{ $no_rm }}</td>
                                        <td>{{ \Carbon\Carbon::parse($tanggal)->format('d-m-Y H:i') }}</td>
                                        <td>Rp {{ number_format($total_biaya, 0, ',', '.') }}</td>
                                        <td>Rp {{ number_format($piutang, 0, ',', '.') }}</td>
                                        <td>{{ $nama_asuransi }}</td>
                                        <td>
                                            <a href="{{ route('piutang.detail', $id) }}"
                                                class="btn btn-sm btn-primary">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            Data tidak ditemukan
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        {{-- Pagination (hanya tampil jika $data dikirim dari controller) --}}
                        @isset($data)
                            @if ($data->hasPages() || $data->total() > 0)
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="text-muted small">
                                        Menampilkan {{ $data->firstItem() ?? 0 }} - {{ $data->lastItem() ?? 0 }} dari
                                        {{ $data->total() }} data
                                    </div>
                                    <div>
                                        {{ $data->appends(['status' => $statusFilter ?? 'belum'])->links() }}
                                    </div>
                                </div>
                            @endif
                        @endisset

                    </div>
                </div>

            </div>
        </div>
    </div>

@endsection
