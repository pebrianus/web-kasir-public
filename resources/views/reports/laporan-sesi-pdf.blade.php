<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Sesi {{ $sesi->nama_sesi }}</title>
    <style>
        /* CSS Sederhana untuk PDF */
        body { font-family: Arial, sans-serif; font-size: 10pt; }
        .header-laporan { text-align: center; margin-bottom: 20px; }
        .header-laporan h5, .header-laporan h6, .header-laporan p { margin: 2px 0; }
        .info-bar { margin-bottom: 10px; font-size: 9pt; }
        .info-bar .left { float: left; }
        .info-bar .right { float: right; }
        .clear { clear: both; }
        table { width: 100%; border-collapse: collapse; font-size: 9pt; }
        th, td { border: 1px solid #666; padding: 4px 5px; word-wrap: break-word; }
        th { background-color: #f0f0f0; text-align: center; }
        .text-right { text-align: right; }
        tfoot tr { font-weight: bold; background-color: #f9f9f9; }
    </style>
</head>
<body>

    <div class="header-laporan">
        <h5 style="font-size: 12pt; font-weight: bold;">LAPORAN PENERIMAAN PERKASIR</h5>
        <h5 style="font-weight: bold; font-size: 12pt;">RS SUAKA INSAN BANJARMASIN</h5>
        <p>TANGGAL: {{ $sesi->waktu_buka->format('d F Y') }}</p>
        <p>SHIFT: {{ $sesi->nama_sesi }} (Dibuka: {{ $sesi->waktu_buka->format('H:i') }} - Ditutup: {{ $sesi->waktu_tutup ? $sesi->waktu_tutup->format('H:i') : '-' }})</p>
    </div>

    <div class="info-bar">
        <div class="left">KASIR: {{ $jenis_kasir }}</div>
        <div class="right">TANGGAL WAKTU CETAK: {{ now()->format('d-m-Y H:i:s') }}</div>
        <div class="clear"></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No RM</th>
                <th>Nama</th>
                <th>No Tagihan</th>
                <th class="text-right">Tunai (Pasien)</th>
                <th class="text-right">Subsidi RS</th>
                <th class="text-right">Piutang (Asuransi)</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($daftarTransaksi as $index => $tx)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>{{ $tx->norm }}</td>
                    <td>{{ $tx->nama }}</td>
                    <td>{{ $tx->no_tagihan }}</td>
                    <td class="text-right">{{ number_format($tx->tunai, 0, ',', '.') }}</td>
                    <td class="text-right">0</td>
                    <td class="text-right">{{ number_format($tx->piutang, 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center;">Tidak ada transaksi pada sesi ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right">Total</td>
                <td class="text-right">Rp {{ number_format($totals['total_tunai'], 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($totals['total_subsidi'], 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($totals['total_piutang'], 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
