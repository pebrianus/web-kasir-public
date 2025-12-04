<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Rincian Tagihan {{ $head->simgos_tagihan_id }}</title>
    <style>
        @page {
            margin-top: 10px;
            margin-left: 25px;
            margin-right: 25px;
            margin-bottom: 5px;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            line-height: 1.3;
            margin: 0;
            padding-top: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td,
        th {
            padding: 1px 2px;
            vertical-align: top;
            word-wrap: break-word;
        }

        /* Kop Surat */
        .kop {
            border-bottom: 1px solid black;
            padding-bottom: 3px;
            margin-bottom: 5px;
        }

        .logo {
            width: 50px;
            float: left;
            margin-right: 10px;
        }

        .kop h4,
        .kop p {
            margin: 0;
            font-weight: bold;
        }

        .clear {
            clear: both;
        }

        /* Header Rincian */
        .header-kuitansi {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 8px;
        }

        /* Info Pasien */
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

        /* Rincian */
        .rincian-table {
            margin-top: 5px;
            border-top: 1px dashed black;
            border-bottom: 1px dashed black;
        }

        .rincian-table th {
            border-bottom: 1px dashed black;
            padding: 3px 2px;
        }

        .rincian-table td {
            padding: 3px 2px;
        }

        .no {
            width: 5%;
            text-align: center;
        }

        .uraian {
            width: 45%;
        }

        .qty {
            width: 10%;
            text-align: right;
        }

        .harga {
            width: 15%;
            text-align: right;
        }

        .subtotal {
            width: 25%;
            text-align: right;
        }

        /* Total */
        .total-table {
            margin-top: 5px;
        }

        .total-table td {
            padding: 2px 4px;
        }

        .total-label {
            text-align: right;
            padding-right: 10px;
        }

        .total-value {
            text-align: right;
            font-weight: bold;
            width: 25%;
        }

        .pasien-penjamin {
            text-align: right;
            font-size: 11pt;
        }

        /* TTD */
        .ttd-table {
            margin-top: 5px;
        }

        .ttd-table td {
            text-align: center;
            width: 50%;
            padding-top: 2px;
        }

        .signature-space {
            height: 30px;
        }

        /* Footer */
        .footer-info {
            margin-top: 2px;
            font-size: 8pt;
        }

        .group-title td {
            font-weight: bold;
            padding-top: 6px;
            padding-bottom: 2px;
            border: none !important;
        }

        .group-subtotal td {
            font-weight: bold;
            text-align: right;
            padding-top: 4px;
            border-top: 1px dashed #aaa;
        }
    </style>
</head>

<body>

    {{-- 1. KOP SURAT --}}
    <div class="kop">
        <img src="{{ public_path('images/logo-rs.png') }}" class="logo">
        <div class="rs-info">
            <h4>RS SUAKA INSAN</h4>
            <p>Jl. Zafri zam zam no. 60 Banjarmasin Kec. Banjarmasin Barat</p>
            <p>Telp. 0511-3354654</p>
        </div>
        <div class="clear"></div>
    </div>

    {{-- 2. HEADER --}}
    <div class="header-kuitansi">
        Rincian Tagihan {{ $jenis_kasir_text }}
    </div>

    {{-- 3. INFO PASIEN --}}
    <table class="info-table">
        <tr>
            <td class="label">No RM</td>
            <td class="separator">:</td>
            <td class="value">{{ $head->simgos_norm }}</td>

            <td class="label">No. Tagihan</td>
            <td class="separator">:</td>
            <td class="value">{{ $head->simgos_tagihan_id }}</td>
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

    {{-- 4. RINCIAN GROUP PER JENIS TARIF --}}
    <table class="rincian-table">
        <thead>
            <tr>
                <th class="no">No</th>
                <th class="uraian">Uraian</th>
                <th class="qty">Qty</th>
                <th class="harga">Harga Satuan</th>
                <th class="subtotal">Total</th>
            </tr>
        </thead>
        <tbody>

            @php $rowNum = 1; @endphp

            @foreach ($detailByJenis as $jenis => $items)

                {{-- NAMA JENIS TARIF --}}
                <tr>
                    <td colspan="5" style="font-weight:bold; padding-top:8px;">
                        {{ $jenisTarifList[$jenis] ?? 'Lainnya' }}
                    </td>
                </tr>

                @php
                    $subtotalJenis = 0;
                @endphp

                {{-- ITEM-ITEM DALAM JENIS TARIF --}}
                @foreach ($items as $d)
                    <tr>
                        <td class="no">{{ $rowNum++ }}</td>
                        <td class="uraian">{{ $d['uraian'] }}</td>

                        <td class="qty">
                            {{ number_format($d['qty'], 0, ',', '.') }}
                        </td>

                        <td class="harga">
                            Rp. {{ number_format($d['harga'], 2, ',', '.') }}
                        </td>

                        <td class="subtotal">
                            Rp. {{ number_format($d['dibayar'], 2, ',', '.') }}
                        </td>
                    </tr>

                    @php
                        $subtotalJenis += $d['dibayar'];
                    @endphp
                @endforeach

                {{-- SUBTOTAL TIAP JENIS TARIF --}}
                <tr>
                    <td colspan="4" style="text-align:right; font-weight:bold; padding-top:4px;">
                        Subtotal {{ $jenisTarifList[$jenis] ?? '' }}
                    </td>
                    <td style="text-align:right; font-weight:bold; padding-top:4px;">
                        Rp. {{ number_format($subtotalJenis, 2, ',', '.') }}
                    </td>
                </tr>

                {{-- SPASI PEMBATAS --}}
                <tr>
                    <td colspan="5" style="height:6px;"></td>
                </tr>

            @endforeach

        </tbody>
    </table>





    {{-- 5. TOTAL --}}
    <table class="total-table">
        {{-- Baris 1: Selalu tampilkan jumlah total kotor --}}
        <tr>
            <td class="total-label">Jumlah Total</td>
            <td class="total-value">
                Rp. {{ number_format($grandTotal, 0, ',', '.') }}
            </td>
        </tr>

        {{-- Baris 2 (opsional): Diskon hanya jika kuitansi pasien dan diskon > 0 --}}
        @if ($tipeKuitansi == 'Pasien' && $head->diskon_simgos > 0)
            <tr>
                <td class="total-label">Potongan / Diskon</td>
                <td class="total-value">
                    - Rp. {{ number_format($head->diskon_simgos, 0, ',', '.') }}
                </td>
            </tr>

            {{-- Hitung total bersih setelah diskon --}}
            @php
                $totalBersih = max(0, $grandTotal - $head->diskon_simgos);
            @endphp

            {{-- Baris 3: Total akhir pasien --}}
            <tr>
                <td class="total-label pasien-penjamin">Tagihan Pasien</td>
                <td class="total-value">
                    Rp. {{ number_format($totalBersih, 0, ',', '.') }}
                </td>
            </tr>

        @else
            {{-- TANPA diskon: Pasien/Asuransi langsung pakai grandTotal --}}
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
            <td></td>
            <td>Kasir</td>
        </tr>
        <tr>
            <td class="signature-space"></td>
            <td class="signature-space"></td>
        </tr>
        <tr>
            <td></td>
            <td>( {{ $namaKasir }} )</td>
        </tr>
    </table>

    {{-- FOOTER --}}
    <div class="footer-info">
        <p>Ket: Harga obat sudah termasuk PPN | Waktu : {{ \Carbon\Carbon::now()->format('H:i:s') }}</p>
    </div>

</body>

</html>
