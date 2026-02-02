<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rincian Lab {{ $head->simgos_tagihan_id }}</title>
    <style>
        @page {
            margin: 10px 25px 5px 25px;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            line-height: 1.3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td, th {
            padding: 2px 3px;
            vertical-align: top;
        }

        .kop {
            border-bottom: 1px solid black;
            margin-bottom: 6px;
        }

        .logo {
            width: 50px;
            float: left;
            margin-right: 10px;
        }

        .clear { clear: both; }

        .header-kuitansi {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 6px;
        }

        .info-table td {
            padding-bottom: 2px;
        }

        .rincian-table {
            margin-top: 6px;
            border-top: 1px dashed black;
            border-bottom: 1px dashed black;
        }

        .rincian-table th {
            border-bottom: 1px dashed black;
        }

        .no { width: 5%; text-align: center; }
        .uraian { width: 35%; }
        .qty { width: 8%; text-align: right; }
        .harga,
        .pasien,
        .asuransi,
        .subtotal { width: 13%; text-align: right; }

        .total-table {
            margin-top: 6px;
        }

        .total-label {
            text-align: right;
            padding-right: 10px;
        }

        .total-value {
            text-align: right;
            font-weight: bold;
        }
    </style>
</head>

<body>

{{-- KOP --}}
<div class="kop">
    <img src="{{ public_path('images/logo-rs.png') }}" class="logo">
    <strong>RS SUAKA INSAN</strong><br>
    Jl. Zafri Zam Zam No. 60 Banjarmasin<br>
    Telp. 0511-3354654
    <div class="clear"></div>
</div>

{{-- HEADER --}}
<div class="header-kuitansi">
    RINCIAN PEMERIKSAAN LABORATORIUM
</div>

{{-- INFO PASIEN --}}
<table class="info-table">
    <tr>
        <td>No RM</td><td>:</td><td>{{ $head->simgos_norm }}</td>
        <td>No Tagihan</td><td>:</td><td>{{ $head->simgos_tagihan_id }}</td>
    </tr>
    <tr>
        <td>Nama</td><td>:</td><td>{{ $head->nama_pasien }}</td>
        <td>Penjamin</td><td>:</td><td>{{ $head->nama_asuransi }}</td>
    </tr>
    <tr>
        <td>Tanggal</td><td>:</td>
        <td>{{ \Carbon\Carbon::parse($head->simgos_tanggal_tagihan)->format('d-m-Y') }}</td>
        <td>Ruangan</td><td>:</td><td>{{ $head->nama_ruangan }}</td>
    </tr>
</table>

{{-- RINCIAN --}}
<table class="rincian-table">
    <thead>
        <tr>
            <th class="no">No</th>
            <th class="uraian">Uraian</th>
            <th class="qty">Qty</th>
            <th class="harga">Harga</th>
            <th class="pasien">Pasien</th>
            <th class="asuransi">Asuransi</th>
            <th class="subtotal">Total</th>
        </tr>
    </thead>
    <tbody>

        @php $no = 1; $subtotalLab = 0; @endphp

        @foreach ($detailByJenis[8] ?? [] as $d)
            <tr>
                <td class="no">{{ $no++ }}</td>
                <td class="uraian">{{ $d['uraian'] }}</td>
                <td class="qty">{{ $d['qty'] }}</td>
                <td class="harga">Rp {{ number_format($d['harga'], 0, ',', '.') }}</td>
                <td class="pasien">Rp {{ number_format($d['dibayar_pasien'], 0, ',', '.') }}</td>
                <td class="asuransi">Rp {{ number_format($d['dibayar_asuransi'], 0, ',', '.') }}</td>
                <td class="subtotal">Rp {{ number_format($d['total'], 0, ',', '.') }}</td>
            </tr>

            @php $subtotalLab += $d['total']; @endphp
        @endforeach

        <tr>
            <td colspan="6" class="total-label">Total Pemeriksaan Lab</td>
            <td class="total-value">Rp {{ number_format($subtotalLab, 0, ',', '.') }}</td>
        </tr>
    </tbody>
</table>

{{-- TTD --}}
<table class="total-table">
    <tr>
        <td colspan="2" class="total-label">
            Banjarmasin, {{ \Carbon\Carbon::now()->format('d F Y') }}
        </td>
    </tr>
</table>

<br>

<table width="100%">
    <tr>
        <td width="70%"></td>
        <td align="center">
            Kasir<br><br><br>
            ( {{ $namaKasir }} )
        </td>
    </tr>
</table>

</body>
</html>
