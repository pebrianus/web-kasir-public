<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Rincian Perbandingan Asuransi {{ $head->simgos_tagihan_id }}</title>
    <style>
        @page {
            margin-top: 10px;
            margin-left: 20px;
            margin-right: 20px;
            margin-bottom: 5px;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            line-height: 1.3;
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td,
        th {
            padding: 1px 3px;
            vertical-align: top;
            word-wrap: break-word;
        }

        /* KOP */
        .kop {
            border-bottom: 1px solid black;
            padding-bottom: 3px;
            margin-bottom: 5px;
        }

        .logo {
            width: 45px;
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

        /* HEADER */
        .header-kuitansi {
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 6px;
        }

        /* INFO PASIEN */
        .info-table .label {
            width: 12%;
            font-size: 9pt;
        }

        .info-table .separator {
            width: 1%;
        }

        .info-table .value {
            width: 30%;
            font-size: 9pt;
        }

        /* RINCIAN */
        .rincian-table {
            margin-top: 6px;
            border-top: 1px dashed black;
            border-bottom: 1px dashed black;
            font-size: 8.5pt;
        }

        .rincian-table th {
            border-bottom: 1px dashed black;
            padding: 3px 3px;
            /* background-color: #f0f0f0; */
            text-align: center;
        }

        .rincian-table td {
            padding: 2px 3px;
        }

        /* Kolom */
        .col-no {
            width: 3%;
            text-align: center;
        }

        .col-uraian {
            width: 30%;
        }

        .col-qty {
            width: 6%;
            text-align: right;
        }

        .col-harga {
            width: 13%;
            text-align: right;
        }

        .col-total {
            width: 13%;
            text-align: right;
        }

        .col-asuransi {
            width: 13%;
            text-align: right;
        }

        .col-selisih {
            width: 13%;
            text-align: right;
        }

        /* Group */
        .group-title td {
            font-weight: bold;
            padding-top: 7px;
            padding-bottom: 2px;
            font-size: 8.5pt;
            /* background-color: #f7f7f7; */
        }

        .group-subtotal td {
            font-weight: bold;
            text-align: right;
            padding: 3px 3px;
            border-top: 1px dashed #999;
            font-size: 8.5pt;
            /* background-color: #fafafa; */
        }

        .group-subtotal .label-sub {
            text-align: right;
        }

        /* Grand Total */
        .grand-total-table {
            margin-top: 6px;
            font-size: 9pt;
        }

        .grand-total-table td {
            padding: 2px 4px;
        }

        .gt-label {
            text-align: right;
            font-weight: bold;
            width: 57%;
        }

        .gt-value {
            text-align: right;
            font-weight: bold;
            width: 13%;
        }

        /* TTD */
        .ttd-table {
            margin-top: 8px;
        }

        .ttd-table td {
            text-align: center;
            width: 50%;
            padding-top: 2px;
        }

        .signature-space {
            height: 45px;
        }

        /* Footer */
        .footer-info {
            margin-top: 2px;
            font-size: 7.5pt;
        }
    </style>
</head>

<body>

    {{-- KOP --}}
    <div class="kop">
        <img src="{{ public_path('images/logo-rs.png') }}" class="logo">
        <div>
            <h4>RS SUAKA INSAN</h4>
            <p>Jl. Zafri zam zam no. 60 Banjarmasin Kec. Banjarmasin Barat</p>
            <p>Telp. 0511-3354654</p>
        </div>
        <div class="clear"></div>
    </div>

    {{-- JUDUL --}}
    <div class="header-kuitansi">
        Rincian Tagihan {{ $jenis_kasir_text }}
    </div>

    {{-- INFO PASIEN --}}
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

    {{-- TABEL RINCIAN --}}
    <table class="rincian-table">
        <thead>
            <tr>
                <th class="col-no">No</th>
                <th class="col-uraian" style="text-align:left;">Uraian</th>
                <th class="col-qty" style="text-align: right">Qty</th>
                <th class="col-harga" style="text-align: right">Harga Satuan</th>
                <th class="col-total" style="text-align: right">Harga Total</th>
                <th class="col-asuransi" style="text-align: right">Tanggungan Asuransi</th>
                <th class="col-selisih" style="text-align: right">Selisih Biaya</th>
            </tr>
        </thead>
        <tbody>
            @php $rowNum = 1; @endphp

            @foreach ($detailByJenis as $jenis => $items)

                {{-- NAMA GROUP --}}
                <tr class="group-title">
                    <td colspan="7">{{ $jenisTarifList[$jenis] ?? 'Lainnya' }}</td>
                </tr>

                @php
                    $subTotal = 0;
                    $subAsuransi = 0;
                    $subSelisih = 0;
                @endphp

                {{-- ITEM --}}
                @foreach ($items as $d)
                    <tr>
                        <td class="col-no">{{ $rowNum++ }}</td>
                        <td class="col-uraian">{{ $d['uraian'] }}</td>
                        <td class="col-qty">{{ number_format($d['qty'], 0, ',', '.') }}</td>
                        <td class="col-harga">{{ number_format($d['harga'], 2, ',', '.') }}</td>
                        <td class="col-total">{{ number_format($d['subtotal'], 2, ',', '.') }}</td>
                        <td class="col-asuransi">{{ number_format($d['asuransi'], 2, ',', '.') }}</td>
                        <td class="col-selisih">{{ number_format($d['selisih'], 2, ',', '.') }}</td>
                    </tr>
                    @php
                        $subTotal += $d['subtotal'];
                        $subAsuransi += $d['asuransi'];
                        $subSelisih += $d['selisih'];
                    @endphp
                @endforeach

                {{-- SUBTOTAL GROUP --}}
                <tr class="group-subtotal">
                    <td colspan="4" class="label-sub">
                        Subtotal {{ $jenisTarifList[$jenis] ?? '' }}
                    </td>
                    <td class="col-total">{{ number_format($subTotal, 2, ',', '.') }}</td>
                    <td class="col-asuransi">{{ number_format($subAsuransi, 2, ',', '.') }}</td>
                    <td class="col-selisih">{{ number_format($subSelisih, 2, ',', '.') }}</td>
                </tr>

                {{-- Spasi antar group --}}
                <tr>
                    <td colspan="7" style="height:5px;"></td>
                </tr>

            @endforeach
        </tbody>
    </table>

    {{-- GRAND TOTAL --}}
    <table class="grand-total-table">
        <tr>
            <td class="gt-label">Grand Total Harga</td>
            <td class="gt-value">{{ number_format($grandTotalHarga, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="gt-label">Total Tanggungan Asuransi ({{ $head->nama_asuransi }})</td>
            <td class="gt-value">{{ number_format($grandTotalAsuransi, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="gt-label" style="border-top: 1px solid black; padding-top:4px;">
                <i>DISCOUNT</i>
            </td>
            <td class="gt-value" style="border-top: 1px solid black; padding-top:4px;">
                {{ number_format($grandTotalSelisih, 2, ',', '.') }}
            </td>
        </tr>
        <tr>
            <td colspan="2" style="text-align:right; padding-top:10px;">
                Banjarmasin, {{ \Carbon\Carbon::now()->format('d F Y') }}
            </td>
        </tr>
    </table>

    {{-- TTD --}}
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
        <p>Ket: Harga obat sudah termasuk PPN | Waktu: {{ \Carbon\Carbon::now()->format('H:i:s') }}</p>
    </div>

</body>

</html>
