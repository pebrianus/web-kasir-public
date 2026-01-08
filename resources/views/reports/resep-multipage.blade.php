<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Resep {{ $jenis_kasir_text }}</title>

    <style>
        @page {
            margin-top: 30px;
            margin-bottom: 30px;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13pt;
            line-height: 1.4;
            margin: 0 auto;
        }

        .page {
            margin-left: 70px;
            margin-right: 70px;
        }

        .kop {
            border-bottom: 2px solid black;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .logo {
            width: 60px;
            float: left;
            margin-right: 10px;
        }

        .rs-info h4 {
            margin: 0;
            font-size: 16pt;
            font-weight: bold;
        }

        .rs-info p {
            margin: 0;
            font-size: 10pt;
        }

        .clear {
            clear: both;
        }

        .title {
            margin-top: 10px;
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 15px;
        }

        table.info td {
            padding: 2px 4px;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
        }

        .divider {
            border-top: 2px solid black;
            margin: 10px 0;
        }

        .resep-item {
            margin-bottom: 15px;
        }

        .Rsign {
            font-size: 20pt;
            font-weight: bold;
            margin-right: 10px;
        }

        .obat-nama {
            font-size: 13pt;
            font-weight: bold;
        }

        .aturan {
            margin-left: 30px;
            font-size: 12pt;
        }

        .dokter {
            margin-top: 40px;
            text-align: right;
            font-size: 11pt;
        }
    </style>
</head>

<body>

@foreach ($resepPages as $pageIndex => $page)

    @php
        $kunjungan   = $page['kunjungan'];
        $resepItems  = $page['resepItems'];
        $order       = $page['order'];
        $racikanNo   = 1;
    @endphp

    <div class="page">

        {{-- KOP --}}
        <div class="kop">
            <img src="{{ public_path('images/logo-rs.png') }}" class="logo">
            <div class="rs-info">
                <h4>RS SUAKA INSAN</h4>
                <p>Jl. Zafri Zam Zam No. 60 Banjarmasin</p>
                <p>Telp. 0511-3354654</p>
            </div>
            <div class="clear"></div>
        </div>

        {{-- TITLE --}}
        <div class="title">RESEP {{ strtoupper($jenis_kasir_text) }}</div>

        {{-- INFO PASIEN --}}
        <table class="info" width="100%">
            <tr>
                <td width="22%" class="label">No. RM</td>
                <td width="3%">:</td>
                <td>{{ $head->simgos_norm }}</td>

                <td width="10%" class="label">Tanggal</td>
                <td width="3%">:</td>
                <td>{{ \Carbon\Carbon::parse($order->TANGGAL)->format('d-m-Y') }}</td>
            </tr>

            <tr>
                <td class="label">Nama</td>
                <td>:</td>
                <td>{{ $head->nama_pasien }}</td>

                <td class="label">Waktu</td>
                <td>:</td>
                <td>{{ \Carbon\Carbon::parse($order->TANGGAL)->format('H:i') }}</td>
            </tr>
        </table>

        <div class="divider"></div>

        {{-- LIST OBAT --}}
        @forelse ($resepItems as $resep)

            {{-- OBAT TUNGGAL --}}
            @if ($resep['type'] === 'tunggal')
                <div class="resep-item">
                    <div>
                        <span class="Rsign">R/</span>
                        <span class="obat-nama">
                            {{ $resep['data']->nama_obat }}
                            [Jumlah {{ rtrim(rtrim($resep['data']->JUMLAH, '0'), '.') }}]
                        </span>
                    </div>

                    <div class="aturan">
                        {{ $resep['data']->DOSIS }}
                        - {{ $resep['data']->nama_frekuensi }}
                        - {{ $resep['data']->nama_rute_pemberian }}
                    </div>
                </div>
            @endif

            {{-- RACIKAN --}}
            @if ($resep['type'] === 'racikan')
                <div class="resep-item">
                    <div>
                        <span class="Rsign">R/{{ $racikanNo }}</span>
                    </div>

                    @foreach ($resep['items'] as $item)
                        <div class="obat-nama" style="margin-left:30px;">
                            {{ $item->nama_obat }}
                        </div>

                        <div class="aturan">
                            {{ $item->DOSIS }}

                            @if ($item->nama_frekuensi)
                                - {{ $item->nama_frekuensi }}
                            @endif

                            @if ($item->nama_rute_pemberian)
                                - {{ $item->nama_rute_pemberian }}
                            @endif

                            @if ($item->KETERANGAN)
                                ({{ $item->KETERANGAN }})
                            @endif

                            @if ($item->nama_petunjuk_racikan)
                                [{{ $item->nama_petunjuk_racikan }}]
                            @endif
                        </div>
                    @endforeach
                </div>

                @php $racikanNo++; @endphp
            @endif

        @empty
            <p>Tidak ada obat.</p>
        @endforelse

        {{-- TTD --}}
        <div class="dokter">
            <br><br>
        </div>

    </div>

    {{-- PAGE BREAK --}}
    @if (!$loop->last)
        <div style="page-break-after: always;"></div>
    @endif

@endforeach

</body>
</html>
