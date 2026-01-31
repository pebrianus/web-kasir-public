<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Jasa Radiologi</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .header h3 {
            margin: 0;
            font-size: 15px;
            font-weight: bold;
        }

        .header p {
            margin: 2px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 0.5px solid #000;
            padding: 4px;
            vertical-align: top;
        }

        th {
            text-align: center;
            font-weight: bold;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }

        .bg-light {
            background-color: #f2f2f2;
        }

        * {
            background: none !important;
        }
    </style>
</head>

<body>

{{-- HEADER --}}
<div class="header">
    <h3>LAPORAN JASA RADIOLOGI</h3>
    <p>
        Periode :
        {{ \Carbon\Carbon::parse($tanggalDari)->format('d-m-Y') }}
        s/d
        {{ \Carbon\Carbon::parse($tanggalSampai)->format('d-m-Y') }}
    </p>
    <p>Asuransi : {{ $asuransi ?? 'Semua' }}</p>
</div>

<table>
    <thead>
        <tr>
            <th width="5%">No</th>
            <th width="15%">No. RM</th>
            <th width="30%">Nama Pasien</th>
            <th width="15%">Tgl Reg</th>
            <th width="20%">Cara Bayar</th>
            <th width="15%">Jasa</th>
        </tr>
    </thead>

    <tbody>
        @php
            $no = 1;
            $grandTotal = 0;
        @endphp

        @forelse ($data as $row)
            {{-- HEADER PASIEN --}}
            <tr class="bg-light">
                <td class="text-center">{{ $no++ }}</td>
                <td>{{ $row['no_rm'] }}</td>
                <td>{{ $row['nama_pasien'] }}</td>
                <td class="text-center">
                    {{ \Carbon\Carbon::parse($row['tanggal_tagihan'])->format('d-m-Y') }}
                </td>
                <td>{{ $row['nama_asuransi'] }}</td>
                <td class="text-right bold">
                    {{ number_format($row['total_fee'], 0, ',', '.') }}
                </td>
            </tr>

            {{-- DETAIL TINDAKAN --}}
            @foreach ($row['tindakan'] as $tdk)
                <tr>
                    <td colspan="3" style="padding-left:15px">
                        • {{ $tdk['nama_tindakan'] }}<br>
                        <small>
                            {{ \Carbon\Carbon::parse($tdk['tanggal'])->format('d-m-Y H:i') }}
                        </small>
                    </td>
                    <td colspan="2">
                        @forelse ($tdk['petugas'] as $p)
                            {{ $p['nama'] }}
                            <small>
                                ({{ $p['jenis'] == 1 ? 'Dokter' : 'Perawat' }})
                            </small><br>
                        @empty
                            <em>-</em>
                        @endforelse
                    </td>
                    <td class="text-right">
                        {{ number_format($tdk['fee_petugas'], 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach

            @php
                $grandTotal += $row['total_fee'];
            @endphp

        @empty
            <tr>
                <td colspan="6" class="text-center">
                    Data tidak ditemukan
                </td>
            </tr>
        @endforelse
    </tbody>

    @if (count($data))
        <tfoot>
            <tr class="bg-light">
                <td colspan="5" class="text-right bold">
                    TOTAL JASA BERSIH
                </td>
                <td class="text-right bold">
                    {{ number_format($grandTotal, 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    @endif
</table>

{{-- FOOTER --}}


</body>
</html>
