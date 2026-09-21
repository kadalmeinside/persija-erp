<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cetak Laporan - {{ $pengajuan->nomor_pengajuan }}</title>
    <style>
        @page { margin: 15mm; }
        body {
            font-family: sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.3;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .logo-container {
            width: 65px;
            height: 65px;
            border: 1px solid #ddd;
            text-align: center;
            vertical-align: middle;
            background-color: #f9f9f9;
        }
        .logo-img {
            max-width: 60px;
            max-height: 60px;
        }
        .company-info {
            padding-left: 15px;
            vertical-align: top;
        }
        .company-title {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #333;
        }
        .company-name {
            font-size: 10pt;
            font-weight: bold;
            margin-top: 2px;
            color: #444;
        }
        .company-addr {
            font-size: 8pt;
            color: #666;
            margin-top: 2px;
        }
        .meta-info {
            text-align: right;
            vertical-align: top;
        }
        .ref-no {
            font-size: 12pt;
            font-weight: bold;
            font-family: monospace;
        }
        .status-badge {
            display: inline-block;
            border: 1px solid #333;
            padding: 3px 6px;
            font-size: 8pt;
            font-weight: bold;
            margin-top: 5px;
            text-transform: uppercase;
        }
        
        /* Grid Layout using Tables */
        .grid-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 20px;
        }
        .grid-col {
            width: 48%;
            vertical-align: top;
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 4px;
        }
        .grid-gap {
            width: 4%;
        }
        .section-header {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #666;
            border-bottom: 1px solid #eee;
            margin-bottom: 8px;
            padding-bottom: 4px;
        }
        .info-row {
            margin-bottom: 4px;
        }
        .info-label {
            display: inline-block;
            width: 80px;
            font-size: 9pt;
            color: #666;
        }
        .info-val {
            font-weight: bold;
            font-size: 9pt;
        }

        /* Items Table */
        .items-wrapper {
            margin-bottom: 20px;
        }
        .items-header-title {
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
            margin-bottom: 5px;
        }
        .table-items {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }
        .table-items th {
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            padding: 6px;
            text-align: left;
        }
        .table-items td {
            border: 1px solid #ccc;
            padding: 6px;
            vertical-align: top;
        }
        .amount-col {
            text-align: right;
            font-family: monospace;
            white-space: nowrap;
        }

        /* Signatures */
        .signatures-table {
            width: 100%;
            margin-top: 40px;
            text-align: center;
        }
        .sig-box {
            vertical-align: top;
            width: 33%;
        }
        .sig-line {
            height: 70px;
        }
        .sig-name {
            font-weight: bold;
            text-decoration: underline;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 7pt;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    
    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <td style="width: 70px;">
                <div class="logo-container">
                    @if(isset($company['logo_path']) && $company['logo_path'])
                        <img src="{{ $company['logo_path'] }}" class="logo-img">
                    @else
                        <span style="font-size:8pt; font-weight:bold; color:#ccc;">LOGO</span>
                    @endif
                </div>
            </td>
            <td class="company-info">
                <div class="company-title">Formulir Laporan Penggunaan Dana</div>
                <div class="company-name">{{ $company['name'] }}</div>
                <div class="company-addr">{{ $company['address'] }}</div>
            </td>
            <td class="meta-info">
                <div class="ref-no">#{{ $pengajuan->nomor_pengajuan }}</div>
                <div style="font-size: 9pt; margin-top: 2px;">Tgl Lapor: {{ \Carbon\Carbon::parse($laporan->tgl_laporan)->translatedFormat('d F Y') }}</div>
                <div class="status-badge">{{ $pengajuan->status_global }}</div>
            </td>
        </tr>
    </table>

    <!-- INFO BLOCKS -->
    <table class="grid-table">
        <tr>
            <!-- COL 1: PELAPOR -->
            <td class="grid-col">
                <div class="section-header">Informasi Pelapor (Penerima Dana)</div>
                <div class="info-row">
                    <span class="info-label">Nama</span>
                    <span class="info-val">: {{ $pengajuan->karyawanPenerima->nama_lengkap ?? $pengajuan->pengaju->nama_lengkap }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Jabatan</span>
                    <span class="info-val">: {{ $pengajuan->karyawanPenerima->jabatan ?? $pengajuan->pengaju->jabatan }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Departemen</span>
                    <span class="info-val">: {{ $pengajuan->departemen->nama_departemen }}</span>
                </div>
            </td>
            
            <td class="grid-gap"></td>

            <!-- COL 2: SUMMARY -->
            <td class="grid-col">
                <div class="section-header">Ringkasan Uang Muka</div>
                <div class="info-row">
                    <span class="info-label">Keperluan</span>
                    <span class="info-val">: {{ $pengajuan->judul_pengajuan }}</span>
                </div>
                <div class="info-row" style="margin-top: 5px; border-top: 1px dashed #ccc; padding-top: 5px;">
                    <span class="info-label">Uang Muka</span>
                    <span class="info-val">: Rp {{ number_format($laporan->total_realisasi_aktual + $laporan->selisih, 0, ',', '.') }}</span>
                </div>
            </td>
        </tr>
    </table>

    <!-- ITEMS -->
    <div class="items-wrapper">
        <div class="items-header-title">
            Rincian Realisasi (Kuitansi/Bon)
        </div>
        <table class="table-items">
            <thead>
                <tr>
                    <th style="width: 30px; text-align: center;">No</th>
                    <th>Deskripsi Item</th>
                    <th style="width: 35%;">Beban Anggaran (COA)</th>
                    <th style="width: 120px; text-align: right;">Nominal Realisasi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($laporan->detail as $idx => $item)
                <tr>
                    <td style="text-align: center;">{{ $idx + 1 }}</td>
                    <td>{{ $item->deskripsi_bon }}</td>
                    <td style="font-size: 8pt;">
                        <div style="font-weight: bold; color: #555;">{{ $item->programKerja->nama_program ?? '-' }}</div>
                        <div>{{ $item->akunGl->kode_akun ?? '' }} - {{ $item->akunGl->nama_akun ?? '' }}</div>
                    </td>
                    <td class="amount-col">
                        Rp {{ number_format($item->nominal_bon, 0, ',', '.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                 <tr style="background-color: #f9f9f9; font-weight: bold;">
                    <td colspan="3" style="text-align: right; text-transform: uppercase; padding-right: 10px;">Total Realisasi Aktual</td>
                    <td class="amount-col">Rp {{ number_format($laporan->total_realisasi_aktual, 0, ',', '.') }}</td>
                </tr>
                <tr style="font-weight: bold; background-color: {{ $laporan->selisih < 0 ? '#fff0f0' : ($laporan->selisih > 0 ? '#f0fff0' : '#f9f9f9') }}">
                    <td colspan="3" style="text-align: right; padding-right: 10px;">
                        @if($laporan->selisih < 0)
                            SELISIH (KURANG BAYAR / REIMBURSE)
                        @elseif($laporan->selisih > 0)
                            SELISIH (LEBIH BAYAR / KEMBALIKAN KE PERUSAHAAN)
                        @else
                            SELISIH (IMPAS)
                        @endif
                    </td>
                    <td class="amount-col">Rp {{ number_format(abs($laporan->selisih), 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- SIGNATURES -->
    <table class="signatures-table">
        <tr>
            <td class="sig-box">
                <div>Dibuat Oleh (Pelapor),</div>
                <div class="sig-line"></div>
                <div class="sig-name">{{ $pengajuan->karyawanPenerima->nama_lengkap ?? $pengajuan->pengaju->nama_lengkap }}</div>
            </td>
            
            <td class="sig-box">
                <div>Diperiksa Oleh (Finance),</div>
                <div class="sig-line" style="padding-top: 5px; min-height: 60px;">
                    @if($laporan->verify_uuid)
                        @php
                            $url = route('public.verify', ['uuid' => $laporan->verify_uuid]);
                        @endphp
                        <img src="data:image/svg+xml;base64, {{ base64_encode(QrCode::format('svg')->size(60)->generate($url)) }} " />
                    @endif
                </div>
                <div class="sig-name">
                    @if($laporan->verifier)
                        {{ $laporan->verifier->nama_lengkap }}
                    @else
                        ( ........................... )
                    @endif
                </div>
                @if($laporan->verified_at)
                    <div style="font-size: 8pt; color: #666;">Tgl: {{ \Carbon\Carbon::parse($laporan->verified_at)->format('d/m/Y') }}</div>
                @endif
            </td>

            @if($laporan->selisih < 0)
                @foreach($pengajuan->approvalProcess as $approval)
                    <td class="sig-box">
                        <div>{{ $approval->label_aksi ?? 'Disetujui Oleh' }},</div>
                        <div class="sig-line" style="padding-top: 5px; min-height: 60px;">
                            @if($approval->status == 'Approved' && $approval->uuid)
                                @php
                                    $url = route('public.verify', ['uuid' => $approval->uuid]);
                                @endphp
                                <img src="data:image/svg+xml;base64, {{ base64_encode(QrCode::format('svg')->size(60)->generate($url)) }} " />
                            @endif
                        </div>
                        <div class="sig-name">
                            @if($approval->status == 'Approved')
                                {{ $approval->actionKaryawan->nama_lengkap ?? '(System)' }}
                            @else
                                ( {{ $approval->targetKaryawan->nama_lengkap ?? '...........................' }} )
                            @endif
                        </div>
                        @if($approval->status == 'Approved')
                            <div style="font-size: 8pt; color: #666;">Tgl: {{ $approval->tgl_aksi ? \Carbon\Carbon::parse($approval->tgl_aksi)->format('d/m/Y') : '-' }}</div>
                        @endif
                    </td>
                @endforeach
            @endif
        </tr>
    </table>

    <div class="footer">
        Dicetak pada: {{ $timestamp }} | Sistem ERP Persija
    </div>

</body>
</html>
