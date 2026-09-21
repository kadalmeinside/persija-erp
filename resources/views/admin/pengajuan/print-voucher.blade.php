<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Voucher Pembayaran - {{ $pengajuan->nomor_pengajuan }}</title>
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
        
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    
    <!-- ========================================== -->
    <!-- PAGE 1: VOUCHER PEMBAYARAN (FINANCE VIEW)  -->
    <!-- ========================================== -->
    
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
                <div class="company-title">VOUCHER PEMBAYARAN (BANK DISBURSEMENT)</div>
                <div class="company-name">{{ $company['name'] }}</div>
                <div class="company-addr">{{ $company['address'] }}</div>
            </td>
            <td class="meta-info">
                <div class="ref-no">#{{ $pengajuan->nomor_pengajuan }}</div>
                <div style="font-size: 9pt; margin-top: 2px;">Cetak: {{ $timestamp }}</div>
                <div class="status-badge" style="background-color: #000; color: #fff;">{{ $pengajuan->status_global }}</div>
            </td>
        </tr>
    </table>

    <table class="grid-table">
        <tr>
            <td class="grid-col">
                <div class="section-header">Dibayarkan Kepada</div>
                <div class="info-row">
                    <span class="info-label">Nama</span>
                    <span class="info-val" style="text-transform: uppercase;">: {{ $pengajuan->vendorPenerima->nama_vendor ?? ($pengajuan->karyawanPenerima->nama_lengkap ?? ($pengajuan->pengaju->nama_lengkap ?? '-')) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Bank</span>
                    <span class="info-val">: {{ $pengajuan->bank_tujuan ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">No. Rekening</span>
                    <span class="info-val" style="font-family: monospace; font-size: 11pt;">: {{ $pengajuan->no_rek_tujuan ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Atas Nama</span>
                    <span class="info-val" style="text-transform: uppercase;">: {{ $pengajuan->atas_nama_tujuan ?? '-' }}</span>
                </div>
            </td>
            
            <td class="grid-gap"></td>

            <td class="grid-col">
                <div class="section-header">Sumber Dana (Kas/Bank)</div>
                @php
                    // Ambil pembayaran terakhir atau gabungkan jika banyak
                    $totalDibayar = $pengajuan->pembayaran->sum('nominal_bayar');
                    $kasBanks = $pengajuan->pembayaran->pluck('kasBank.nama_bank')->unique()->filter()->implode(', ');
                    $tglBayars = $pengajuan->pembayaran->pluck('tgl_bayar')->unique()->map(function($d) {
                        return \Carbon\Carbon::parse($d)->format('d/m/Y');
                    })->implode(', ');
                @endphp
                <div class="info-row">
                    <span class="info-label">Total Dibayar</span>
                    <span class="info-val" style="font-size: 12pt;">: Rp {{ number_format($totalDibayar, 0, ',', '.') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tanggal Bayar</span>
                    <span class="info-val">: {{ $tglBayars ?: '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Akun Bank</span>
                    <span class="info-val">: {{ $kasBanks ?: 'Tunai/Kas' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Keterangan</span>
                    <span class="info-val">: Pembayaran Pengajuan {{ $pengajuan->nomor_pengajuan }} ({{ $pengajuan->judul_pengajuan }})</span>
                </div>
            </td>
        </tr>
    </table>

    <div class="items-wrapper">
        <div class="items-header-title">Riwayat / Rincian Eksekusi Pembayaran</div>
        <table class="table-items">
            <thead>
                <tr>
                    <th style="width: 30px; text-align: center;">No</th>
                    <th style="width: 100px;">Tanggal</th>
                    <th>Sumber Kas/Bank</th>
                    <th>Catatan Finance</th>
                    <th style="width: 120px; text-align: right;">Nominal Keluar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pengajuan->pembayaran as $idx => $bayar)
                <tr>
                    <td style="text-align: center;">{{ $idx + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($bayar->tgl_bayar)->format('d/m/Y') }}</td>
                    <td>{{ $bayar->kasBank->nama_bank ?? 'Tunai' }} ({{ $bayar->kasBank->nomor_rekening ?? '-' }})</td>
                    <td>{{ $bayar->catatan ?: '-' }}</td>
                    <td class="amount-col">Rp {{ number_format($bayar->nominal_bayar, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #999;">Belum ada data pembayaran (Ini tidak seharusnya terjadi jika status sudah Paid)</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="background-color: #f9f9f9; font-weight: bold;">
                    <td colspan="4" style="text-align: right; padding-right: 10px;">Total Pembayaran</td>
                    <td class="amount-col">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Tanda Tangan Voucher -->
    <table class="signatures-table" style="margin-top: 60px;">
        <tr>
            <td class="sig-box">
                <div>Prepared By (Finance/Cashier),</div>
                <div class="sig-line"></div>
                <div class="sig-name">( ........................... )</div>
            </td>
            <td class="sig-box">
                <div>Checked By (Finance Manager),</div>
                <div class="sig-line"></div>
                <div class="sig-name">( ........................... )</div>
            </td>
            <td class="sig-box">
                <div>Received By (Penerima),</div>
                <div class="sig-line"></div>
                <div class="sig-name">( ........................... )</div>
            </td>
        </tr>
    </table>

    <!-- ========================================== -->
    <!-- PAGE 2: FORMULIR PENGAJUAN (ASLI)          -->
    <!-- ========================================== -->
    <div class="page-break"></div>

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
                <div class="company-title">Formulir Pengajuan Dana</div>
                <div class="company-name">{{ $company['name'] }}</div>
                <div class="company-addr">{{ $company['address'] }}</div>
            </td>
            <td class="meta-info">
                <div class="ref-no">#{{ $pengajuan->nomor_pengajuan }}</div>
                <div style="font-size: 9pt; margin-top: 2px;">Tanggal: {{ \Carbon\Carbon::parse($pengajuan->tgl_pengajuan)->translatedFormat('d F Y') }}</div>
                <div class="status-badge">{{ $pengajuan->status_global }}</div>
            </td>
        </tr>
    </table>

    <table class="grid-table">
        <tr>
            <td class="grid-col">
                <div class="section-header">Informasi Pemohon</div>
                <div class="info-row"><span class="info-label">Nama</span><span class="info-val">: {{ $pengajuan->pengaju->nama_lengkap }}</span></div>
                <div class="info-row"><span class="info-label">ID / NIK</span><span class="info-val">: {{ $pengajuan->pengaju->nomor_induk_karyawan ?? '-' }}</span></div>
                <div class="info-row"><span class="info-label">Jabatan</span><span class="info-val">: {{ $pengajuan->pengaju->jabatan }}</span></div>
                <div class="info-row"><span class="info-label">Departemen</span><span class="info-val">: {{ $pengajuan->departemen->nama_departemen }}</span></div>
            </td>
            <td class="grid-gap"></td>
            <td class="grid-col">
                <div class="section-header">Detail Pembayaran</div>
                <div class="info-row"><span class="info-label">Tipe</span><span class="info-val" style="text-transform:uppercase;">: {{ $pengajuan->tipe_pengajuan === 'Langsung' ? 'Pembayaran Langsung' : 'Uang Muka (CA)' }}</span></div>
                <div class="info-row"><span class="info-label">Metode</span><span class="info-val" style="text-transform:uppercase;">: {{ $pengajuan->metode_pembayaran }}</span></div>
                <div class="info-row"><span class="info-label">Keperluan</span><span class="info-val">: {{ $pengajuan->judul_pengajuan }}</span></div>
            </td>
        </tr>
    </table>

    <div class="items-wrapper">
        <div class="items-header-title">Rincian Item Biaya</div>
        <table class="table-items">
            <thead>
                <tr>
                    <th style="width: 30px; text-align: center;">No</th>
                    <th>Deskripsi Item</th>
                    <th style="width: 35%;">Beban Anggaran (COA)</th>
                    <th style="width: 120px; text-align: right;">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pengajuan->detail as $idx => $item)
                <tr>
                    <td style="text-align: center;">{{ $idx + 1 }}</td>
                    <td>{{ $item->deskripsi_item }}</td>
                    <td style="font-size: 8pt;">
                        <div style="font-weight: bold; color: #555;">{{ $item->programKerja->nama_program ?? '-' }}</div>
                        <div>{{ $item->akunGl->kode_akun ?? '' }} - {{ $item->akunGl->nama_akun ?? '' }}</div>
                    </td>
                    <td class="amount-col">Rp {{ number_format($item->nominal_item, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                @php
                    $originalTotal = $pengajuan->total_nominal_diajukan;
                    if ($pengajuan->tipe_pengajuan === 'UangMuka' && $pengajuan->laporanPenggunaan) {
                        $originalTotal = $pengajuan->laporanPenggunaan->total_realisasi_aktual + $pengajuan->laporanPenggunaan->selisih;
                    }
                @endphp
                 <tr style="background-color: #f9f9f9; font-weight: bold;">
                    <td colspan="3" style="text-align: right; text-transform: uppercase; padding-right: 10px;">Total Pengajuan Awal</td>
                    <td class="amount-col">Rp {{ number_format($originalTotal, 0, ',', '.') }}</td>
                </tr>
                @if($originalTotal != $pengajuan->total_nominal_diajukan)
                 <tr style="background-color: #fff3cd; font-weight: bold;">
                    <td colspan="3" style="text-align: right; text-transform: uppercase; padding-right: 10px;">Total Tagihan Akhir (Setelah Revisi/Reimburse)</td>
                    <td class="amount-col">Rp {{ number_format($pengajuan->total_nominal_diajukan, 0, ',', '.') }}</td>
                </tr>
                @endif
            </tfoot>
        </table>
    </div>

    @if($pengajuan->catatan_header && in_array($pengajuan->status_global, ['Revision', 'Rejected']))
    <div style="border: 1px dashed #bbb; padding: 10px; border-radius: 4px; font-size: 9pt; background-color: #fffdf0; margin-bottom: 20px;">
        <strong>Catatan:</strong> <span style="font-style: italic;">"{{ $pengajuan->catatan_header }}"</span>
    </div>
    @endif

    <table class="signatures-table">
        <tr>
            <td class="sig-box">
                <div>Diajukan Oleh,</div>
                <div class="sig-line"></div>
                <div class="sig-name">{{ $pengajuan->pengaju->nama_lengkap }}</div>
                <div style="font-size: 8pt; color: #666;">Tgl: {{ \Carbon\Carbon::parse($pengajuan->created_at)->format('d/m/Y') }}</div>
            </td>
            @foreach($pengajuan->approvalProcess as $approval)
                <td class="sig-box">
                    <div>{{ $approval->label_aksi ?? 'Approver' }},</div>
                    <div class="sig-line" style="padding-top: 5px; min-height: 60px;">
                        @if($approval->status == 'Approved' && $approval->uuid)
                            @php $url = route('public.verify', ['uuid' => $approval->uuid]); @endphp
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
            @if($pengajuan->approvalProcess->isEmpty())
                <td class="sig-box"><div>Diketahui (Dept Head),</div><div class="sig-line"></div><div class="sig-name">( ........................... )</div></td>
                <td class="sig-box"><div>Disetujui (Finance),</div><div class="sig-line"></div><div class="sig-name">( ........................... )</div></td>
            @endif
        </tr>
    </table>


    <!-- ========================================== -->
    <!-- PAGE 3: BUKTI TRANSFER                     -->
    <!-- ========================================== -->
    @if(count($buktiPembayaran) > 0)
    <div class="page-break"></div>
    <div style="border-bottom: 2px solid #333; margin-bottom: 20px; padding-bottom: 10px;">
        <span style="font-size: 14pt; font-weight: bold; text-transform: uppercase;">Bukti Transfer / Pembayaran</span>
        <div style="font-size: 9pt; color: #555;">Ref No: #{{ $pengajuan->nomor_pengajuan }}</div>
    </div>

    @foreach($buktiPembayaran as $bukti)
    <div style="text-align: center; border: 1px solid #ddd; background-color: #f9f9f9; padding: 20px; min-height: 400px; margin-bottom: 20px;">
        @if($bukti['is_image'])
            <img src="{{ $bukti['path'] }}" style="max-width: 100%; max-height: 800px; object-fit: contain;">
        @else
            <div style="padding: 50px; color: #666;">
                <h3 style="margin-top:0;">File Bukti Transfer Tersedia</h3>
                <p>Silakan unduh file asli melalui sistem ERP untuk melihat detail bukti (Format PDF/Lainnya).</p>
                <div style="font-family: monospace; background: #eee; padding: 10px; margin-top: 10px; display: inline-block;">
                    {{ $bukti['filename'] }}
                </div>
            </div>
        @endif
    </div>
    @endforeach
    @endif


    <!-- ========================================== -->
    <!-- PAGE 4: LAMPIRAN AWAL (INVOICE)            -->
    <!-- ========================================== -->
    @if($attachmentPath)
    <div class="page-break"></div>
    <div style="border-bottom: 2px solid #333; margin-bottom: 20px; padding-bottom: 10px;">
        <span style="font-size: 14pt; font-weight: bold; text-transform: uppercase;">Lampiran Pengajuan (Awal)</span>
        <div style="font-size: 9pt; color: #555;">Ref No: #{{ $pengajuan->nomor_pengajuan }}</div>
    </div>

    <div style="text-align: center; border: 1px solid #ddd; background-color: #f9f9f9; padding: 20px; min-height: 400px;">
        @if($isImage)
            <img src="{{ $attachmentPath }}" style="max-width: 100%; max-height: 800px; object-fit: contain;">
        @else
            <div style="padding: 50px; color: #666;">
                <h3 style="margin-top:0;">File Lampiran Tersedia</h3>
                <p>Lampiran berupa file PDF atau format lain yang tidak dapat ditampilkan langsung di cetakan ini.</p>
                <p>Silakan unduh file asli melalui sistem ERP untuk melihat detail lampiran.</p>
                <div style="font-family: monospace; background: #eee; padding: 10px; margin-top: 10px; display: inline-block;">
                    {{ basename($pengajuan->attachment_path) }}
                </div>
            </div>
        @endif
    </div>
    @endif

    <!-- ========================================== -->
    <!-- PAGE 5: FORMULIR LAPORAN (SETTLEMENT)      -->
    <!-- ========================================== -->
    @if($pengajuan->laporanPenggunaan)
    <div class="page-break"></div>

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
                <div style="font-size: 9pt; margin-top: 2px;">Tgl Lapor: {{ \Carbon\Carbon::parse($pengajuan->laporanPenggunaan->tgl_laporan)->translatedFormat('d F Y') }}</div>
                <div class="status-badge">{{ $pengajuan->status_global }}</div>
            </td>
        </tr>
    </table>

    <table class="grid-table">
        <tr>
            <td class="grid-col">
                <div class="section-header">Informasi Pelapor (Penerima Dana)</div>
                <div class="info-row"><span class="info-label">Nama</span><span class="info-val">: {{ $pengajuan->karyawanPenerima->nama_lengkap ?? $pengajuan->pengaju->nama_lengkap }}</span></div>
                <div class="info-row"><span class="info-label">Jabatan</span><span class="info-val">: {{ $pengajuan->karyawanPenerima->jabatan ?? $pengajuan->pengaju->jabatan }}</span></div>
                <div class="info-row"><span class="info-label">Departemen</span><span class="info-val">: {{ $pengajuan->departemen->nama_departemen }}</span></div>
            </td>
            <td class="grid-gap"></td>
            <td class="grid-col">
                <div class="section-header">Ringkasan Uang Muka</div>
                <div class="info-row"><span class="info-label">Keperluan</span><span class="info-val">: {{ $pengajuan->judul_pengajuan }}</span></div>
                <div class="info-row" style="margin-top: 5px; border-top: 1px dashed #ccc; padding-top: 5px;"><span class="info-label">Uang Muka</span><span class="info-val">: Rp {{ number_format($pengajuan->laporanPenggunaan->total_realisasi_aktual + $pengajuan->laporanPenggunaan->selisih, 0, ',', '.') }}</span></div>
            </td>
        </tr>
    </table>

    <div class="items-wrapper">
        <div class="items-header-title">Rincian Realisasi (Kuitansi/Bon)</div>
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
                @foreach($pengajuan->laporanPenggunaan->detail as $idx => $item)
                <tr>
                    <td style="text-align: center;">{{ $idx + 1 }}</td>
                    <td>{{ $item->deskripsi_bon }}</td>
                    <td style="font-size: 8pt;">
                        <div style="font-weight: bold; color: #555;">{{ $item->programKerja->nama_program ?? '-' }}</div>
                        <div>{{ $item->akunGl->kode_akun ?? '' }} - {{ $item->akunGl->nama_akun ?? '' }}</div>
                    </td>
                    <td class="amount-col">Rp {{ number_format($item->nominal_bon, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                 <tr style="background-color: #f9f9f9; font-weight: bold;">
                    <td colspan="3" style="text-align: right; text-transform: uppercase; padding-right: 10px;">Total Realisasi Aktual</td>
                    <td class="amount-col">Rp {{ number_format($pengajuan->laporanPenggunaan->total_realisasi_aktual, 0, ',', '.') }}</td>
                </tr>
                <tr style="font-weight: bold; background-color: {{ $pengajuan->laporanPenggunaan->selisih < 0 ? '#fff0f0' : ($pengajuan->laporanPenggunaan->selisih > 0 ? '#f0fff0' : '#f9f9f9') }}">
                    <td colspan="3" style="text-align: right; padding-right: 10px;">
                        @if($pengajuan->laporanPenggunaan->selisih < 0)
                            SELISIH (KURANG BAYAR / REIMBURSE)
                        @elseif($pengajuan->laporanPenggunaan->selisih > 0)
                            SELISIH (LEBIH BAYAR / KEMBALIKAN KE PERUSAHAAN)
                        @else
                            SELISIH (IMPAS)
                        @endif
                    </td>
                    <td class="amount-col">Rp {{ number_format(abs($pengajuan->laporanPenggunaan->selisih), 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- SIGNATURES SETTLEMENT -->
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
                    @if($pengajuan->laporanPenggunaan->verify_uuid)
                        @php
                            $url = route('public.verify', ['uuid' => $pengajuan->laporanPenggunaan->verify_uuid]);
                        @endphp
                        <img src="data:image/svg+xml;base64, {{ base64_encode(QrCode::format('svg')->size(60)->generate($url)) }} " />
                    @endif
                </div>
                <div class="sig-name">
                    @if($pengajuan->laporanPenggunaan->verifier)
                        {{ $pengajuan->laporanPenggunaan->verifier->nama_lengkap }}
                    @else
                        ( ........................... )
                    @endif
                </div>
                @if($pengajuan->laporanPenggunaan->verified_at)
                    <div style="font-size: 8pt; color: #666;">Tgl: {{ \Carbon\Carbon::parse($pengajuan->laporanPenggunaan->verified_at)->format('d/m/Y') }}</div>
                @endif
            </td>

            @if($pengajuan->laporanPenggunaan->selisih < 0)
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
    
    <!-- ========================================== -->
    <!-- PAGE 6: BUKTI BON/KUITANSI LAPORAN         -->
    <!-- ========================================== -->
    @if(isset($buktiLaporan) && count($buktiLaporan) > 0)
        <div class="page-break"></div>
        <div style="border-bottom: 2px solid #333; margin-bottom: 20px; padding-bottom: 10px;">
            <span style="font-size: 14pt; font-weight: bold; text-transform: uppercase;">Lampiran Realisasi (Bon/Kuitansi)</span>
            <div style="font-size: 9pt; color: #555;">Ref No: #{{ $pengajuan->nomor_pengajuan }}</div>
        </div>

        @foreach($buktiLaporan as $idx => $bukti)
        <div style="margin-bottom: 30px; page-break-inside: avoid;">
            <div style="background-color: #f0f0f0; padding: 10px; border: 1px solid #ddd; font-weight: bold; font-size: 10pt;">
                Item {{ $idx + 1 }}: {{ $bukti['deskripsi'] }} (Rp {{ number_format($bukti['nominal'], 0, ',', '.') }})
            </div>
            <div style="text-align: center; border: 1px solid #ddd; border-top: none; padding: 15px; background-color: #fcfcfc;">
                @if($bukti['is_image'])
                    <img src="{{ $bukti['path'] }}" style="max-width: 100%; max-height: 500px; object-fit: contain;">
                @else
                    <div style="padding: 30px; color: #666;">
                        <h3 style="margin-top:0;">File Bukti Tersedia</h3>
                        <div style="font-family: monospace; background: #eee; padding: 10px; display: inline-block;">
                            {{ $bukti['filename'] }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
        @endforeach
    @endif
    
    @endif

    <div class="footer">
        Dicetak pada: {{ $timestamp }} | Dokumen Lengkap (Voucher, Form, Lampiran) | Sistem ERP Persija
    </div>

</body>
</html>
