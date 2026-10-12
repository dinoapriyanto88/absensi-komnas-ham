<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi Kehadiran Peserta</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 25px 25px 35px 25px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #222;
            line-height: 1.45;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #8b1e2d;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .institution {
            font-size: 12px;
            font-weight: bold;
            color: #8b1e2d;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .report-title {
            font-size: 16px;
            font-weight: bold;
            margin: 4px 0;
            text-transform: uppercase;
        }

        .subtitle {
            font-size: 9px;
            color: #555;
        }

        .activity-name {
            font-size: 13px;
            font-weight: bold;
            margin-top: 10px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .info-table td {
            padding: 5px 7px;
            vertical-align: top;
            border: 1px solid #ddd;
        }

        .info-label {
            width: 15%;
            background: #f3f4f6;
            font-weight: bold;
        }

        .info-value {
            width: 35%;
        }

        .section-title {
            font-size: 10px;
            font-weight: bold;
            margin: 10px 0 7px;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .attendance-table thead {
            display: table-header-group;
        }

        .attendance-table th {
            background: #8b1e2d;
            color: #fff;
            font-size: 8px;
            font-weight: bold;
            text-align: center;
            padding: 7px 4px;
            border: 1px solid #741725;
        }

        .attendance-table td {
            border: 1px solid #cfd3d8;
            padding: 6px 4px;
            font-size: 8px;
            vertical-align: middle;
            overflow-wrap: break-word;
        }

        .attendance-table tbody tr:nth-child(even) {
            background: #f7f8fa;
        }

        .center {
            text-align: center;
        }

        .name {
            font-weight: bold;
        }

        .signature {
            width: 75px;
            height: 38px;
            object-fit: contain;
        }

        .empty {
            text-align: center;
            padding: 18px !important;
            color: #666;
        }

        .footer {
            margin-top: 12px;
            padding-top: 7px;
            border-top: 1px solid #ccc;
            font-size: 8px;
            color: #666;
        }

        .page-number {
            position: fixed;
            bottom: -22px;
            left: 0;
            right: 0;
            text-align: right;
            font-size: 8px;
            color: #666;
        }

        .no {
            width: 4%;
        }

        .participant {
            width: 16%;
        }

        .category {
            width: 9%;
        }

        .position {
            width: 12%;
        }

        .institution-column {
            width: 17%;
        }

        .identity {
            width: 11%;
        }

        .time {
            width: 13%;
        }

        .signature-column {
            width: 10%;
        }
    </style>
</head>

<body>

    @php
        $jumlahPeserta = count($baris);

        $tanggalKegiatan = $kegiatan->tanggal ?: '-';

        if ($kegiatan->mulai_at) {
            $tanggalKegiatan = \Carbon\Carbon::parse($kegiatan->mulai_at)
                ->locale('id')
                ->translatedFormat('l, d F Y');
        }

        $waktuMulai = $kegiatan->mulai_at
            ? \Carbon\Carbon::parse($kegiatan->mulai_at)->format('H:i')
            : null;

        $waktuSelesai = $kegiatan->selesai_at
            ? \Carbon\Carbon::parse($kegiatan->selesai_at)->format('H:i')
            : null;

        $rentangWaktu = '-';

        if ($waktuMulai && $waktuSelesai) {
            $rentangWaktu = $waktuMulai . '–' . $waktuSelesai . ' WIB';
        } elseif ($waktuMulai) {
            $rentangWaktu = $waktuMulai . ' WIB';
        }
    @endphp

    <div class="header">
        <div class="institution">KOMISI NASIONAL HAK ASASI MANUSIA</div>

        <div class="report-title">
            Rekapitulasi Kehadiran Peserta
        </div>

        <div class="subtitle">
            Laporan daftar kehadiran peserta kegiatan
        </div>

        <div class="activity-name">
            {{ $kegiatan->nama }}
        </div>
    </div>

    <table class="info-table">
        <tr>
            <td class="info-label">Hari/Tanggal</td>
            <td class="info-value">{{ $tanggalKegiatan }}</td>

            <td class="info-label">Waktu Kegiatan</td>
            <td class="info-value">{{ $rentangWaktu }}</td>
        </tr>

        <tr>
            <td class="info-label">Jumlah Peserta</td>
            <td class="info-value">
                <strong>{{ $jumlahPeserta }} orang</strong>
            </td>

            <td class="info-label">Tanggal Cetak</td>
            <td class="info-value">
                {{ \Carbon\Carbon::now('Asia/Jakarta')
                    ->locale('id')
                    ->translatedFormat('d F Y, H:i') }} WIB
            </td>
        </tr>

        @if($kegiatan->deskripsi)
            <tr>
                <td class="info-label">Keterangan</td>
                <td colspan="3">{{ $kegiatan->deskripsi }}</td>
            </tr>
        @endif
    </table>

    <div class="section-title">DAFTAR KEHADIRAN PESERTA</div>

    <table class="attendance-table">
        <thead>
            <tr>
                <th class="no">No.</th>
                <th class="participant">Nama Peserta</th>
                <th class="category">Jenis Peserta</th>
                <th class="position">Jabatan</th>
                <th class="institution-column">Instansi / Unit Kerja</th>
                <th class="identity">NIP / NIK</th>
                <th class="time">Waktu Kehadiran</th>
                <th class="signature-column">Tanda Tangan</th>
            </tr>
        </thead>

        <tbody>
            @forelse($baris as $index => $item)
                @php
                    $peserta = $item['data'];
                @endphp

                <tr>
                    <td class="center">{{ $index + 1 }}</td>

                    <td class="name">
                        {{ $peserta->nama ?: '-' }}
                    </td>

                    <td class="center">
                        {{ $peserta->jenis_peserta ?: '-' }}
                    </td>

                    <td>
                        {{ $peserta->jabatan ?: '-' }}
                    </td>

                    <td>
                        @if($peserta->instansi && $peserta->unit_kerja)
                            {{ $peserta->instansi }} /
                            {{ $peserta->unit_kerja }}
                        @elseif($peserta->instansi)
                            {{ $peserta->instansi }}
                        @elseif($peserta->unit_kerja)
                            {{ $peserta->unit_kerja }}
                        @else
                            -
                        @endif
                    </td>

                    <td class="center">
                        {{ $peserta->nip_nik ?: '-' }}
                    </td>

                    <td class="center">
                        {{ $peserta->created_at
                            ? \Carbon\Carbon::parse($peserta->created_at)
                                ->timezone('Asia/Jakarta')
                                ->format('d-m-Y H:i:s') . ' WIB'
                            : '-' }}
                    </td>

                    <td class="center">
                        @if(!empty($item['ttd']))
                            <img
                                src="{{ $item['ttd'] }}"
                                class="signature"
                                alt="Tanda tangan peserta"
                            >
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty">
                        Belum ada peserta yang tercatat dalam kegiatan ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dokumen ini dibuat secara otomatis oleh Sistem Absensi Digital
        Komisi Nasional Hak Asasi Manusia.
    </div>

    <div class="page-number">
        Halaman <span class="page"></span>
        dari <span class="topage"></span>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $pdf->page_text(
                740,
                575,
                "Halaman {PAGE_NUM} dari {PAGE_COUNT}",
                $font,
                8,
                array(0.35, 0.35, 0.35)
            );
        }
    </script>

</body>
</html>