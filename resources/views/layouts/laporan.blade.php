@extends('layouts.main')

{{-- Layout ini akan menyediakan sub-sidebar untuk semua halaman laporan --}}
@section('content')
    <div class="row">

        {{-- KOLOM KIRI: SUB-SIDEBAR (Sesuai Mockup-mu) --}}
        <div class="col-lg-3">
            <div class="card shadow mb-4 overflow-hidden">
                <div class="list-group list-group-flush">

                    {{-- Gunakan 'request()->routeIs()' untuk menandai menu aktif --}}
                    @if(auth()->user()->role_id == 1)

                    <a href="{{ route('laporan.penerimaan.index', ['jenis' => 1]) }}"
                        class="list-group-item list-group-item-action 
        {{ request('jenis') == 1 ? 'active' : '' }}">
                        Laporan Penerimaan RJ
                    </a>

            @endif

                    @if(auth()->user()->role_id == 2)
<a href="{{ route('laporan.penerimaan.index', ['jenis' => 2]) }}"
    class="list-group-item list-group-item-action {{ request('jenis') == 2 ? 'active' : '' }}">
    Laporan Penerimaan IGD
</a>

                    <a href="{{ route('laporan.penerimaan.index', ['jenis' => 3]) }}"
                        class="list-group-item list-group-item-action 
        {{ request('jenis') == 3 ? 'active' : '' }}">
                        Laporan Penerimaan RI
                    </a>
@endif


                    {{-- <a href="#" class="list-group-item list-group-item-action disabled">
                    Laporan Penerimaan IGD (Nanti) 3
                </a>
                
                <a href="#" class="list-group-item list-group-item-action disabled">
                    Laporan Penerimaan RI (Nanti) 2
                </a> --}}
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: KONTEN LAPORAN SPESIFIK --}}
        <div class="col-lg-9">

            {{-- Di sinilah konten dari file "anak" akan dimuat --}}
            @yield('laporan_content')

        </div>

    </div>
@endsection
