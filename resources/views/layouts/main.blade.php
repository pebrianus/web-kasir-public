<!DOCTYPE html>
<html lang="id"> {{-- Ubah bahasa jadi 'id' --}}

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">

    {{-- Title Halaman Dinamis --}}
    <title>@yield('title', 'Aplikasi Kasir')</title>

    {{-- Path diubah menggunakan asset() --}}
    <link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    {{-- Path diubah menggunakan asset() --}}
    <link href="{{ asset('css/sb-admin-2.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">

    {{-- Style Sticky navbar --}}
    <style>
        .sidebar {
    z-index: 1100 !important;
}

        #accordionSidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            /* opsional: supaya sidebar penuh */
            /* overflow-y: auto; */
            /* sidebar bisa discroll */
        }

        #accordionSidebar .collapse {
            z-index: 1050 !important;
        }
    </style>

</head>

{{-- @php
    // Cek apakah ada sesi kasir yang sedang 'BUKA' di seluruh sistem
    $sesiKasirAktif = \App\Models\KasirSesi::where('status', 'BUKA')->first();
@endphp --}}

<body id="page-top">

    <div id="wrapper">

        {{-- Kita bisa pisahkan ini ke file sendiri nanti, misal: @include('layouts.sidebar') --}}
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ url('/dashboard') }}">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-laugh-wink"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Kasir <sup>RS</sup></div>
            </a>

            <hr class="sidebar-divider my-0">

            <li class="nav-item active">
                <a class="nav-link" href="{{ url('/dashboard') }}">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span></a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                Menu Utama
            </div>

            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo"
                    aria-expanded="true" aria-controls="collapseTwo">
                    <i class="fas fa-fw fa-dollar-sign"></i>
                    <span>Kasir</span>
                </a>
                <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Pembayaran:</h6>

                        {{-- Role 1: hanya Rawat Jalan --}}
                        @if (auth()->user()->role_id == 1)
                            <a class="collapse-item" href="{{ route('pencarian.rawat-jalan', ['jenis' => 1]) }}">
                                Kasir Rawat Jalan
                            </a>
                        @endif

                        {{-- Role 2: Rawat Inap dan IGD --}}
                        @if (auth()->user()->role_id == 2)
                            <a class="collapse-item" href="{{ route('pencarian.rawat-jalan', ['jenis' => 3]) }}">
                                Kasir Rawat Inap
                            </a>

                            <a class="collapse-item" href="{{ route('pencarian.rawat-jalan', ['jenis' => 2]) }}">
                                Kasir IGD
                            </a>
                        @endif

                    </div>

                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseLaporan"
                    aria-expanded="true" aria-controls="collapseLaporan">
                    <i class="fas fa-fw fa-chart-bar"></i>
                    <span>Laporan</span>
                </a>
                <div id="collapseLaporan" class="collapse" aria-labelledby="headingUtilities"
                    data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Laporan Kasir:</h6>
                        {{-- Jenis dynamic ditambahkan nanti --}}
                        <a class="collapse-item"
                            href="{{ route('laporan.penerimaan.index', ['jenis' => auth()->user()->role_id]) }}">
                            Laporan Penerimaan
                        </a>

                    </div>
                </div>
            </li>

            {{-- Hapus menu lain yang tidak perlu, sisakan ini --}}

            <hr class="sidebar-divider d-none d-md-block">

            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
        <div id="content-wrapper" class="d-flex flex-column">

            <div id="content">

                {{-- Kita bisa pisahkan ini ke file sendiri nanti, misal: @include('layouts.navbar') --}}
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <ul class="navbar-nav ml-auto">

                        <li class="nav-item dropdown no-arrow mx-1">
                            @if ($sesiKasirAktif)
                                {{-- JIKA KASIR SEDANG BUKA --}}
                                <a class="nav-link dropdown-toggle" href="#" id="sesiDropdown" role="button"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                    title="Kasir Sedang BUKA. Klik untuk Tutup Kasir.">
                                    <i class="fas fa-cash-register fa-fw text-success"></i> </a>
                                <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                    aria-labelledby="sesiDropdown">
                                    <span class="dropdown-item disabled">
                                        Sesi BUKA (sejak {{ $sesiKasirAktif->waktu_buka->format('H:i') }})
                                    </span>
                                    <div class="dropdown-divider"></div>

                                    {{-- Form untuk "Tutup Kasir" --}}
                                    <a class="dropdown-item" href="{{ route('kasir.sesi.tutup') }}"
                                        onclick="event.preventDefault(); 
                                                if(confirm('Anda yakin ingin menutup sesi kasir saat ini?')) {
                                                    document.getElementById('tutup-kasir-form').submit();
                                                }">
                                        <i class="fas fa-door-closed fa-sm fa-fw mr-2 text-gray-400"></i>
                                        Tutup Kasir
                                    </a>

                                    <form id="tutup-kasir-form" action="{{ route('kasir.sesi.tutup') }}"
                                        method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            @else
                                {{-- JIKA KASIR SEDANG TUTUP --}}
                                <a class="nav-link dropdown-toggle" href="#" role="button"
                                    data-toggle="modal" data-target="#modalBukaKasir" {{-- Memicu Modal --}}
                                    title="Kasir DITUTUP. Klik untuk Buka Sesi.">
                                    <i class="fas fa-cash-register fa-fw text-danger"></i> </a>
                            @endif

                        </li>

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">

                                {{-- Nama User Dinamis --}}
                                <span
                                    class="mr-2 d-none d-lg-inline text-gray-600 small">{{ Auth::user()->nama }}</span>

                                <img class="img-profile rounded-circle" src="{{ asset('img/undraw_profile.svg') }}">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profile
                                </a>
                                <div class="dropdown-divider"></div>
                                {{-- Tombol Logout --}}
                                <a class="dropdown-item" href="#" data-toggle="modal"
                                    data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>

                    </ul>

                </nav>
                <div class="container-fluid">

                    {{-- INI ADALAH BAGIAN KONTEN UTAMA YANG DINAMIS --}}
                    {{-- Semua halaman nanti akan "diisi" di sini --}}
                    @yield('content')

                </div>
            </div>
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; RS SUAKA INSAN BANJARMASIN {{ date('Y') }}</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Siap untuk Keluar?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Pilih "Logout" di bawah jika Anda siap untuk mengakhiri sesi Anda saat ini.
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>

                    {{-- PERBAIKAN TOMBOL LOGOUT AGAR SESUAI LARAVEL --}}
                    <a class="btn btn-primary" href="{{ route('logout') }}"
                        onclick="event.preventDefault();
                                     document.getElementById('logout-form').submit();">
                        Logout
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                    {{-- BATAS PERBAIKAN --}}

                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalBukaKasir" tabindex="-1" role="dialog" aria-labelledby="modalBukaKasirLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">

            {{-- Form ini akan mengirim POST ke rute yg kita buat --}}
            <form action="{{ route('kasir.sesi.buka') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalBukaKasirLabel">Buka Sesi Kasir</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        Anda akan membuka sesi kasir baru untuk shift ini.
                        <br><br>
                        <strong>Waktu Buka:</strong> {{ now()->format('d F Y, H:i') }}
                        <br>
                        <strong>Kasir:</strong> {{ Auth::user()->nama }}
                        <br><br>
                        Lanjutkan?
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
                        <button class="btn btn-primary" type="submit">Ya, Buka Kasir</button>
                    </div>
                </div>
            </form>

        </div>
    </div>

    {{-- Path diubah menggunakan asset() --}}
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    {{-- Path diubah menggunakan asset() --}}
    <script src="{{ asset('vendor/jquery-easing/jquery.easing.min.js') }}"></script>

    {{-- Path diubah menggunakan asset() --}}
    <script src="{{ asset('js/sb-admin-2.min.js') }}"></script>

    <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>

    <script>
        $(document).ready(function() {});
    </script>

    @stack('scripts')

    {{-- Hapus script chart jika tidak dipakai di semua halaman --}}
    {{-- <script src="{{ asset('vendor/chart.js/Chart.min.js') }}"></script> --}}

    {{-- <script src="{{ asset('js/demo/chart-area-demo.js') }}"></script> --}}
    {{-- <script src="{{ asset('js/demo/chart-pie-demo.js') }}"></script> --}}

</body>

</html>
