<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Resep {{ $jenis_kasir_text }}</title>

    <style>
        @page {
            margin-top: 30px;
            margin-bottom: 30px;
            /* margin-left: 70px;
            margin-right: 70px; */
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13pt;
            line-height: 1.4;
            /* width: 690px; */
            /* <= Kunci biar mirip dokumen asli */
            margin: 0 auto;
            /* Tengah */
        }


        /* KOP */
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

        /* JUDUL */
        .title {
            margin-top: 10px;
            font-size: 16pt;
            font-weight: bold;
            text-align: left;
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

        /* OBAT */
        .resep-item {
            margin-bottom: 15px;
        }

        .Rsign {
            font-size: 20pt;
            font-weight: bold;
            margin-right: 10px;
            font-style: italic;
        }

        .obat-nama {
            font-size: 13pt;
            font-weight: bold;
        }

        .aturan {
            margin-left: 30px;
            font-size: 12pt;
        }

        /* TTD */
        .dokter {
            margin-top: 40px;
            text-align: right;
            font-size: 11pt;
        }

        .page {
            margin-left: 70px;
            margin-right: 70px;
        }
    </style>
</head>

<body>

    <!-- HANDLE ERROR MESSAGE -->
    @if (!empty($errorMessage))
        <div class="page">
            <div class="kop">
                <img src="{{ public_path('images/logo-rs.png') }}" class="logo">
                <div class="rs-info">
                    <h4>RS SUAKA INSAN</h4>
                    <p>Jl. Zafri zam zam no. 60 Banjarmasin</p>
                </div>
                <div class="clear"></div>
            </div>

            <div class="title">INFORMASI RESEP</div>

            <div class="divider"></div>

            <p style="font-size:14pt; margin-top:20px;">
                {{ $errorMessage }}
            </p>
        </div>
        @php return; @endphp
    @endif


    <!-- Container -->
    <div class="page">
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

        {{-- 2. TITLE --}}
        <div class="title">RESEP RAWAT JALAN</div>

        {{-- 3. INFO PASIEN --}}
        <table class="info" width="100%">
            <tr>
                <td width="22%" class="label">No. RM</td>
                <td width="3%">:</td>
                <td>{{ $head->simgos_norm }}</td>

                <td width="10%" class="label">Tanggal</td>
                <td width="3%">:</td>
                <td>{{ \Carbon\Carbon::parse($head->simgos_tanggal_tagihan)->format('d-m-Y') }}</td>
            </tr>

            <tr>
                <td class="label">Nama</td>
                <td>:</td>
                <td>{{ $head->nama_pasien }}</td>

                <td class="label">Waktu</td>
                <td>:</td>
                <td>{{ \Carbon\Carbon::parse($head->simgos_tanggal_tagihan)->format('H:i') }}</td>
            </tr>

            <tr>
                <td class="label"></td>
                <td></td>
                <td></td>

                <td class="label"></td>
                <td></td>
                <td></td>
            </tr>

            <tr>
                <td class="label"></td>
                <td></td>
                <td></td>

                <td class="label"></td>
                <td></td>
                <td></td>
            </tr>
        </table>

        <div class="divider"></div>

        {{-- 4. LIST OBAT --}}
        @php $racikanNo = 1; @endphp

        @forelse ($resepItems as $resep)

            {{-- ===================== --}}
            {{-- OBAT TUNGGAL --}}
            {{-- ===================== --}}
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

            {{-- ===================== --}}
            {{-- RACIKAN --}}
            {{-- ===================== --}}
            @if ($resep['type'] === 'racikan')
                <div class="resep-item">

                    {{-- Header Racikan --}}
                    <div>
                        <span class="Rsign">R/{{ $racikanNo }}</span>
                    </div>

                    {{-- List Obat + Aturan per Obat --}}
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
                                <span style="margin-left:5px;">
                                    [{{ $item->nama_petunjuk_racikan }}]
                                </span>
                            @endif
                        </div>


                    @endforeach

                </div>

                @php $racikanNo++; @endphp
            @endif


        @empty
            <p>Tidak ada obat yang dilayani.</p>
        @endforelse


        {{-- 5. TTD DOKTER --}}
        <div class="dokter">
            <br><br>

        </div>
    </div>

</body>

</html>
