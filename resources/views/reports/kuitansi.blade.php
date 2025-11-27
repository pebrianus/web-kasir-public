<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi {{ $head->simgos_tagihan_id }}</title>
    <style>
        @page {
            margin-top: 10px; /* Hapus margin default halaman */
            margin-left: 25px;
            margin-right: 25px;
            margin-bottom: 5px;
        }
        /* Reset dasar & Font */
        body { 
            font-family: Arial, Helvetica, sans-serif; /* Font mirip mesin tik */
            font-size: 11pt; /* Sedikit lebih besar dari sebelumnya */
            line-height: 1.3;
            margin: 0; /* Hapus margin default */
            padding-top: 0;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        td, th { 
            padding: 1px 2px; 
            vertical-align: top;
            word-wrap: break-word; /* <-- PAKSA WRAPPING JIKA PANJANG */
        }

        /* 1. KOP SURAT */
        .kop { 
            border-bottom: 1px solid black; 
            padding-bottom: 3px; 
            margin-bottom: 5px; 
        }
        .kop .logo { 
            width: 50px; /* Sesuaikan ukuran logo */
            float: left; 
            margin-right: 10px; 
        }
        .kop .rs-info { 
            /* float: left; Biarkan mengalir */
        }
        .kop h4, .kop p { 
            margin: 0; 
            font-weight: bold;
        }

        /* 2. HEADER KUITANSI */
        .header-kuitansi { 
            text-align: center; 
            font-weight: bold; 
            font-size: 12pt; 
            margin-bottom: 8px; 
        }

        /* 3. INFO PASIEN/TAGIHAN */
        .info-table td { 
            padding-bottom: 1px;
        }
        .info-table .label { 
            width: 15%; 
        }
        .info-table .separator { 
            width: 2%; 
        }
        .info-table .value { 
            width: 33%; 
        }

        /* 4. TABEL RINCIAN */
        .rincian-table { 
            margin-top: 5px; 
            border-top: 1px dashed black;
            border-bottom: 1px dashed black;
        }
        .rincian-table th { 
            text-align: left; 
            border-bottom: 1px dashed black;
            padding: 3px 2px;
        }
        .rincian-table td { 
            padding: 3px 2px; 
        }
        .rincian-table .no { width: 5%; text-align: center; }
        .rincian-table .uraian { width: 70%; }
        .rincian-table .subtotal { width: 25%; text-align: right; }

        /* 5. BAGIAN TOTAL */
        .total-table { 
            margin-top: 5px; 
        }
        .total-table td { 
            padding: 2px 4px; 
        }
        .total-table .total-label { 
            text-align: right; 
            padding-right: 10px;
        }
        .total-table .total-value { 
            text-align: right; 
            font-weight: bold; 
            width: 25%; 
        }
        .total-table .pasien-penjamin {
            text-align: right;
            font-size: 11pt;
        }

        /* 6. TANDA TANGAN & FOOTER */
        .ttd-table { 
            margin-top: 5px; 
        }
        .ttd-table td { 
            text-align: center; 
            width: 50%; 
            padding-top: 2px; 
        }
        .ttd-table .signature-space { 
            height: 30px; /* Ruang untuk tanda tangan */
        }
        .footer-info { 
            margin-top: 2px; 
            font-size: 8pt; 
        }

        /* Utility */
        .clear { clear: both; }

    </style>
</head>
<body>
    {{-- 1. KOP SURAT --}}
    <div class="kop">
        {{-- Jika punya logo, letakkan di public/images/logo-rs.png --}}
        <img src="{{ public_path('images/logo-rs.png') }}" alt="Logo RS" class="logo">
        <div class="rs-info">
            <h4>RS SUAKA INSAN</h4>
            <p>Jl. Zafri zam zam no. 60 Banjarmasin Kec. Banjarmasin Barat</p>
            <p>Telp. 0511-3354654</p>
        </div>
        <div class="clear"></div>
    </div>

    {{-- 2. HEADER KUITANSI --}}
    <div class="header-kuitansi">
        Kwitansi Rawat Jalan
    </div>

    {{-- 3. INFO PASIEN/TAGIHAN --}}
    <table class="info-table">
        <tr>
            <td class="label">No RM</td>
            <td class="separator">:</td>
            <td class="value">{{ $head->simgos_norm }}</td>
            <td class="label">No. Kwitansi</td>
            <td class="separator">:</td>
            <td class="value">{{ $head->simgos_tagihan_id }}</td> {{-- ID lokal --}}
        </tr>
         <tr>
            <td class="label">Nama</td>
            <td class="separator">:</td>
            <td class="value">{{ $head->nama_pasien }}</td>
            <td class="label">Penjamin</td>
            <td class="separator">:</td>
            <td class="value">{{ $head->nama_asuransi }}</td>
        </tr>
         <tr>
            <td class="label">Tanggal</td>
            <td class="separator">:</td>
            <td class="value">{{ \Carbon\Carbon::parse($head->simgos_tanggal_tagihan)->format('d-m-Y') }}</td>
            <td class="label">Ruangan/Dokter</td>
            <td class="separator">:</td>
            <td class="value">{{ $head->nama_ruangan }} / {{ $head->nama_dokter }}</td>
        </tr>
    </table>

    {{-- 4. TABEL RINCIAN --}}
    <table class="rincian-table">
        <thead>
            <tr>
                <th class="no">No</th>
                <th class="uraian">Item Tagihan</th>
                <th class="subtotal">Sub Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rekap as $index => $item)
                <tr>
                    <td class="no">{{ $index + 1 }}</td>
                    <td class="uraian">{{ $item['uraian'] }}</td>
                    <td class="subtotal">Rp. {{ number_format($item['subtotal'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center;">(Tidak ada rincian)</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- 5. BAGIAN TOTAL --}}
    {{-- <table class="total-table">
         <tr>
            <td class="total-label">Jumlah Total</td>
            <td class="total-value">Rp. {{ number_format($grandTotal, 0, ',', '.') }}</td>
        </tr>
         <tr>
            // Tampilkan Pasien/Penjamin
            <td class="total-label pasien-penjamin">
                 @if ($tipeKuitansi == 'Pasien')
                    Tagihan Pasien
                @else
                    Tagihan {{ $head->nama_asuransi }}
                @endif
            </td>
            <td class="total-value">Rp. {{ number_format($grandTotal, 0, ',', '.') }}</td>
        </tr>
         <tr>
            <td colspan="2" class="total-label" style="padding-top: 10px;">
                Banjarmasin, {{ \Carbon\Carbon::now()->format('d F Y') }}
            </td>
        </tr>
    </table> --}}
    <table class="total-table">
        {{-- Baris 1: Selalu Tampilkan "Jumlah Total" (Subtotal/Kotor) --}}
         <tr>
            <td class="total-label">Jumlah Total</td>
            <td class="total-value">Rp. {{ number_format($grandTotal, 0, ',', '.') }}</td>
        </tr>

        {{-- Baris 2 (Opsional): Tampilkan Diskon jika ada --}}
        @if ($tipeKuitansi == 'Pasien' && $head->diskon_simgos > 0)
            <tr>
                <td class="total-label">Potongan / Diskon</td>
                <td class="total-value">
                    - Rp. {{ number_format($head->diskon_simgos, 0, ',', '.') }}
                </td>
            </tr>
            
            {{-- Hitung Total Bersih --}}
            @php 
                $totalBersih = max(0, $grandTotal - $head->diskon_simgos); 
            @endphp

            {{-- Baris 3: Langsung "Tagihan Pasien" dengan Total Bersih --}}
            <tr>
                <td class="total-label pasien-penjamin">Tagihan Pasien</td>
                <td class="total-value">
                    Rp. {{ number_format($totalBersih, 0, ',', '.') }}
                </td>
            </tr>

        @else
            {{-- Jika Tidak Ada Diskon ATAU Kuitansi Asuransi --}}
            {{-- Baris 3: Langsung Nama Penanggung dengan Total Asli --}}
             <tr>
                <td class="total-label pasien-penjamin">
                    @if ($tipeKuitansi == 'Pasien')
                        Tagihan Pasien
                    @else
                        {{ $head->nama_asuransi }}
                    @endif
                </td>
                <td class="total-value">
                    Rp. {{ number_format($grandTotal, 0, ',', '.') }}
                </td>
            </tr>
        @endif

        {{-- Baris Tanggal --}}
         <tr>
            <td colspan="2" class="total-label" style="padding-top: 10px;">
                Banjarmasin, {{ \Carbon\Carbon::now()->format('d F Y') }}
            </td>
        </tr>
    </table>

    {{-- 6. TANDA TANGAN --}}
    <table class="ttd-table">
        <tr>
            <td>Pasien</td>
            <td>Kasir</td>
        </tr>
        <tr>
            <td class="signature-space"></td>
            <td class="signature-space"></td>
        </tr>
         <tr>
            <td>( {{ $head->nama_pasien }} )</td>
            <td>( {{ $namaKasir }} )</td>
        </tr>
    </table>

    {{-- 7. FOOTER INFO --}}
    <div class="footer-info">
        <p>Ket: Harga obat sudah termasuk PPN   |   Waktu : {{ \Carbon\Carbon::now()->format('H:i:s') }}</p>
        
    </div>

</body>
</html>