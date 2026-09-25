<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Formulir Pengajuan Cuti - {{ $cuti->karyawan->nama_lengkap }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #333;
            line-height: 1.5;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 0 0 5px 0;
            font-size: 22px;
            text-transform: uppercase;
        }
        .header p {
            margin: 0;
            font-size: 14px;
        }
        .info-table, .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 5px 0;
            vertical-align: top;
        }
        .info-table td:first-child {
            width: 150px;
            font-weight: bold;
        }
        .info-table td:nth-child(2) {
            width: 10px;
        }
        .detail-table th, .detail-table td {
            border: 1px solid #000;
            padding: 10px;
        }
        .detail-table th {
            background-color: #f4f4f4;
            text-align: left;
        }
        .signature-box {
            margin-top: 50px;
            width: 100%;
            display: table;
        }
        .signature-col {
            display: table-cell;
            width: 33.33%;
            text-align: center;
        }
        .signature-col p {
            margin: 0;
            margin-bottom: 60px;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            border: 1px solid #000;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }

    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h2>Formulir Pengajuan Cuti</h2>
        <p>No. Registrasi: CUTI-{{ date('Y', strtotime($cuti->created_at)) }}-{{ str_pad($cuti->id, 5, '0', STR_PAD_LEFT) }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td>Nama Pemohon</td>
            <td>:</td>
            <td>{{ $cuti->karyawan->nama_lengkap }}</td>
        </tr>
        <tr>
            <td>NIK</td>
            <td>:</td>
            <td>{{ $cuti->karyawan->nomor_induk_karyawan ?? '-' }}</td>
        </tr>
        <tr>
            <td>Departemen</td>
            <td>:</td>
            <td>{{ $cuti->karyawan->departemen->nama_departemen ?? '-' }}</td>
        </tr>
        <tr>
            <td>Jabatan</td>
            <td>:</td>
            <td>{{ $cuti->karyawan->jabatan ?? '-' }}</td>
        </tr>
        <tr>
            <td>Tgl. Pengajuan</td>
            <td>:</td>
            <td>{{ \Carbon\Carbon::parse($cuti->created_at)->translatedFormat('d F Y') }}</td>
        </tr>
    </table>

    <table class="detail-table">
        <tr>
            <th style="width: 30%;">Jenis Cuti</th>
            <td>{{ $cuti->jenisCuti->nama_cuti }}</td>
        </tr>
        <tr>
            <th>Pelaksanaan</th>
            <td>
                {{ \Carbon\Carbon::parse($cuti->tgl_mulai)->translatedFormat('d F Y') }} 
                s/d 
                {{ \Carbon\Carbon::parse($cuti->tgl_selesai)->translatedFormat('d F Y') }}
            </td>
        </tr>
        <tr>
            <th>Durasi</th>
            <td>{{ $cuti->jumlah_hari }} Hari Kerja</td>
        </tr>
        <tr>
            <th>Alasan / Keperluan</th>
            <td>{{ $cuti->alasan }}</td>
        </tr>
        <tr>
            <th>Status Dokumen</th>
            <td>
                <span class="status-badge">{{ $cuti->status }}</span>
            </td>
        </tr>
    </table>

    <div class="signature-box">
        <!-- Pemohon -->
        <div class="signature-col">
            <p>Pemohon,</p>
            <div style="margin: 5px auto; width: 60px; height: 60px;">
                <img src="data:image/svg+xml;base64,{!! base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(60)->generate(route('public.verify', 'CUTI-' . encrypt($cuti->id)))) !!}" width="60" height="60" alt="QR Pemohon" />
            </div>
            <span style="font-size: 10px; font-style: italic;">Diajukan pada<br>{{ \Carbon\Carbon::parse($cuti->created_at)->format('d/m/Y H:i') }}</span>
            <br>
            <span class="signature-name">{{ $cuti->karyawan->nama_lengkap }}</span>
            <br>
            <span>Karyawan</span>
        </div>

        <!-- Approval Process -->
        @if ($cuti->approvalProcess && $cuti->approvalProcess->count() > 0)
            @foreach ($cuti->approvalProcess as $index => $step)
            <div class="signature-col">
                <p>Tahap {{ $step->tahap }},</p>
                @if ($step->status === 'Approved')
                    <div style="margin: 5px auto; width: 60px; height: 60px;">
                        <img src="data:image/svg+xml;base64,{!! base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(60)->generate(route('public.verify', $step->uuid))) !!}" width="60" height="60" alt="QR" />
                    </div>
                    <span style="color: green; font-size: 10px; font-style: italic;">Disetujui secara elektronik pada<br>{{ \Carbon\Carbon::parse($step->tanggal_proses)->format('d/m/Y H:i') }}</span>
                    <br>
                @elseif ($step->status === 'Rejected')
                    <div style="margin: 5px auto; width: 60px; height: 60px;">
                        <img src="data:image/svg+xml;base64,{!! base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(60)->generate(route('public.verify', $step->uuid))) !!}" width="60" height="60" alt="QR" />
                    </div>
                    <span style="color: red; font-size: 10px; font-style: italic;">Ditolak secara elektronik pada<br>{{ \Carbon\Carbon::parse($step->tanggal_proses)->format('d/m/Y H:i') }}</span>
                    <br>
                @else
                    <br><br><br><br><br>
                @endif
                <span class="signature-name">{{ $step->targetKaryawan->nama_lengkap ?? 'Manajer / Atasan' }}</span>
                <br>
                <span>{{ $step->targetKaryawan->jabatan ?? 'Approver' }}</span>
            </div>
            @endforeach
        @endif
    </div>
</body>
</html>
