<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Cetak Resep</title>
</head>

<body>

    <h2>Data Resep Pasien</h2>

    <p><strong>NOPEN:</strong> {{ $nopen }}</p>
    <p><strong>Kunjungan NOMOR:</strong> {{ $kunjungan->NOMOR }}</p>
    <p><strong>Ruangan:</strong> {{ $kunjungan->RUANGAN }}</p>


    <hr>

    <h3>Daftar Obat</h3>

    <table border="1" cellspacing="0" cellpadding="6" width="100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Kunjungan</th>
                <th>Farmasi</th>
                <th>Tanggal</th>
                <th>Jumlah</th>
                <th>Aturan Pakai</th>
                <th>Dosis</th>
                <th>Keterangan</th>
                <th>Racikan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($farmasi as $item)
                <tr>
                    <td>{{ $item->ID }}</td>
                    <td>{{ $item->KUNJUNGAN }}</td>
                    <td>{{ $item->FARMASI }}</td>
                    <td>{{ $item->TANGGAL }}</td>
                    <td>{{ $item->JUMLAH }}</td>
                    <td>{{ $item->ATURAN_PAKAI }}</td>
                    <td>{{ $item->DOSIS }}</td>
                    <td>{{ $item->KETERANGAN }}</td>
                    <td>{{ $item->RACIKAN }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">Tidak ada data farmasi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>

</html>
