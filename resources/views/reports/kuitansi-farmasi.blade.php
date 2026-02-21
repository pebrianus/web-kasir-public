<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kwitansi Farmasi {{ $tagihan->NOMOR }}</title>

    <style>
        @page {
            margin-top: 20px;
            margin-left: 25px;
            margin-right: 25px;
            margin-bottom: 5px;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            line-height: 1.3;
            margin: 0;
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
            padding-bottom: 3px;
            margin-bottom: 5px;
        }

        .kop .logo {
            width: 50px;
            float: left;
            margin-right: 10px;
        }

        .kop h4, .kop p {
            margin: 0;
            font-weight: bold;
        }

        .header-kuitansi {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 5px;
        }

        .info-table td {
            padding-bottom: 2px;
        }

        .label { width: 15%; }
        .separator { width: 2%; }
        .value { width: 33%; }

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

        .no { width: 5%; text-align: center; }
        .uraian { width: 55%; }
        .qty { width: 10%; text-align: center; }
        .harga { width: 15%; text-align: right; }
        .subtotal { width: 15%; text-align: right; }

        .total-table {
            margin-top: 5px;
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

        .ttd-table {
            margin-top: 10px;
        }

        .ttd-table td {
            text-align: center;
            width: 50%;
        }

        .signature-space {
            height: 50px;
        }

        .clear { clear: both; }
    </style>
</head>

<body>

{{-- 1. KOP --}}
<div class="kop">
    <img src="{{ public_path('images/logo-rs.png') }}" class="logo">
    <div>
        <h4>RS SUAKA INSAN</h4>
        <p>Jl. Zafri Zam Zam No. 60 Banjarmasin</p>
    </div>
    <div class="clear"></div>
</div>

{{-- 2. HEADER --}}
<div class="header-kuitansi">
    KWITANSI FARMASI
</div>

{{-- 3. INFO --}}
<table class="info-table">
    <tr>
        <td class="label">No. Transaksi</td>
        <td class="separator">:</td>
        <td class="value">{{ $tagihan->NOMOR }}</td>

        <td class="label">Tanggal</td>
        <td class="separator">:</td>
        <td class="value">
            {{ \Carbon\Carbon::parse($tagihan->TANGGAL)->format('d-m-Y') }}
        </td>
    </tr>
    <tr>
        <td class="label">Nama Pasien</td>
        <td class="separator">:</td>
        <td class="value">{{ $tagihan->PENGUNJUNG }}</td>

        <td class="label">Dokter</td>
        <td class="separator">:</td>
        <td class="value">{{ $tagihan->DOKTER ?? '-' }}</td>
    </tr>
</table>

{{-- 4. RINCIAN OBAT --}}
@php $grandTotal = 0; @endphp

<table class="rincian-table">
    <thead>
        <tr>
            <th class="no">No</th>
            <th class="uraian">Nama Obat</th>
            <th class="qty">Qty</th>
            <th class="harga">Harga</th>
            <th class="subtotal">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($tagihan->OBAT as $index => $item)
            @php
                $qty = (float) $item->JUMLAH;
                $harga = (float) $item->HARGA_JUAL_BARANG;
                $subtotal = $qty * $harga;
                $grandTotal += $subtotal;
            @endphp
            <tr>
                <td class="no">{{ $index + 1 }}</td>
                <td class="uraian">{{ $item->NAMA_BARANG }}</td>
                <td class="qty">{{ rtrim(rtrim($item->JUMLAH, '0'), '.') }}</td>
                <td class="harga">Rp {{ number_format($harga,0,',','.') }}</td>
                <td class="subtotal">Rp {{ number_format($subtotal,0,',','.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" style="text-align:center">(Tidak ada item obat)</td>
            </tr>
        @endforelse
    </tbody>
</table>

{{-- 5. TOTAL --}}
<table class="total-table">
    <tr>
        <td class="total-label">Jumlah Total</td>
        <td class="total-value">
            Rp {{ number_format($grandTotal,0,',','.') }}
        </td>
    </tr>
    <tr>
        <td colspan="2" class="total-label" style="padding-top:5px;">
            Banjarmasin, {{ \Carbon\Carbon::now()->format('d F Y') }}
        </td>
    </tr>
</table>

{{-- 6. TTD --}}
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
        <td>( {{ auth()->user()->name ?? 'Kasir' }} )</td>
    </tr>
</table>

</body>
</html>
