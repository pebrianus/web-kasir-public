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
            font-size: 16px;
            font-weight: bold;
        }

        .header p {
            margin: 2px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: top;
        }

        table th {
            text-align: center;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        /* Hilangkan SEMUA background */
        * {
            background: none !important;
            background-color: transparent !important;
        }

        /* Optional: garis lebih tipis */
        .thin-border th,
        .thin-border td {
            border-width: 0.5px;
        }

        .no-border {
            border: none !important;
        }

        .no-border td {
            border: none !important;
        }
    </style>

</head>

<body>

    {{-- HEADER --}}
    <div class="header">
        <h3>LAPORAN JASA RADIOLOGI</h3>
        <p>Periode: {{ $tanggalDari ?? '01-01-2026' }} s/d {{ $tanggalSampai ?? '13-01-2026' }}</p>
        <p>Petugas: {{ $petugas ?? 'Semua Petugas' }}</p>
    </div>

    {{-- TABEL --}}
    <table>
        <thead>
            <tr>
                <th style="width:5%">No</th>
                <th style="width:15%">No. RM</th>
                <th style="width:30%">Nama Pasien</th>
                <th style="width:15%">Tgl Reg</th>
                <th style="width:20%">Cara Bayar</th>
                <th style="width:15%">Jasa Dokter</th>
            </tr>
        </thead>

        <tbody>
            {{-- DATA PASIEN --}}
            <tr>
                <td class="text-center">1</td>
                <td>00-35-93-23</td>
                <td>BUDIANSYAH</td>
                <td class="text-center">29-12-2025</td>
                <td>Tanpa Asuransi</td>
                <td class="text-right">120.000</td>
            </tr>

            {{-- DETAIL TINDAKAN --}}
            <tr>
                <td colspan="2"></td>
                <td colspan="3">
                    Pemeriksaan Dokter IGD
                </td>
                <td class="text-right">
                    120.000
                </td>
            </tr>

            {{-- SUBTOTAL --}}
            <tr class="subtotal">
                <td colspan="5" class="text-right bold">
                    Jumlah IGD
                </td>
                <td class="text-right bold">
                    120.000
                </td>
            </tr>

            {{-- TOTAL SEBELUM DISKON --}}
            <tr>
                <td colspan="5" class="text-right">
                    Total Jasa Sebelum Diskon PEBRI DOKTER IGD
                </td>
                <td class="text-right">
                    120.000
                </td>
            </tr>

            {{-- TOTAL BERSIH --}}
            <tr class="total">
                <td colspan="5" class="text-right">
                    Total Jasa Bersih PEBRI DOKTER IGD
                </td>
                <td class="text-right">
                    120.000
                </td>
            </tr>
        </tbody>
    </table>

    {{-- FOOTER --}}
    <br><br>
    <table class="no-border" width="100%">
        <tr>
            <td class="no-border" width="70%"></td>
            <td class="no-border text-center">
                {{ now()->format('d-m-Y') }}<br>
                Petugas<br><br><br>
                <strong>( __________________ )</strong>
            </td>
        </tr>
    </table>

</body>

</html>
