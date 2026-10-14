<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Perjanjian Kerja - {{ $karyawan->nama_lengkap }}</title>
    <style>
        @page {
            margin: 12mm 18mm 16mm 18mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9.5pt;
            line-height: 1.45;
            color: #222222;
        }

        /* ══════════════════════════════════════════════════════════════ */
        /* KOP SURAT RESMI (Identical standard with Pengajuan & Payslip)  */
        /* ══════════════════════════════════════════════════════════════ */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #222222;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .logo-col {
            width: 70px;
            vertical-align: middle;
        }
        .logo-container {
            width: 65px;
            height: 65px;
            border: 1px solid #dddddd;
            text-align: center;
            vertical-align: middle;
            background-color: #fafafa;
        }
        .logo-img {
            max-width: 60px;
            max-height: 60px;
        }
        .logo-fallback {
            font-size: 8pt;
            font-weight: bold;
            color: #aaaaaa;
            line-height: 65px;
        }
        .company-info {
            padding-left: 14px;
            vertical-align: middle;
        }
        .company-title {
            font-size: 13pt;
            font-weight: 800;
            text-transform: uppercase;
            color: #111827;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .company-name {
            font-size: 9.5pt;
            font-weight: bold;
            margin-top: 2px;
            color: #374151;
        }
        .company-addr {
            font-size: 8pt;
            color: #4b5563;
            margin-top: 2px;
            line-height: 1.3;
            white-space: pre-line;
        }
        .meta-info {
            text-align: right;
            vertical-align: middle;
            white-space: nowrap;
        }
        .ref-no {
            font-size: 9.5pt;
            font-weight: bold;
            font-family: monospace;
            color: #111827;
        }
        .meta-date {
            font-size: 8pt;
            margin-top: 3px;
            color: #4b5563;
        }
        .status-badge {
            display: inline-block;
            border: 1.5px solid #111827;
            padding: 2px 7px;
            font-size: 7.5pt;
            font-weight: 800;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background-color: #f3f4f6;
        }

        /* ══════════════════════════════════════════════════════════════ */
        /* JUDUL & NOMOR SURAT                                           */
        /* ══════════════════════════════════════════════════════════════ */
        .doc-title-wrapper {
            text-align: center;
            margin-top: 8px;
            margin-bottom: 16px;
        }
        .doc-title {
            font-size: 11.5pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            text-decoration: underline;
            color: #111827;
        }
        .doc-sub {
            font-size: 9pt;
            font-weight: 600;
            color: #374151;
            margin-top: 4px;
            font-family: monospace;
        }

        /* ══════════════════════════════════════════════════════════════ */
        /* PIHAK IDENTITAS TABLE                                         */
        /* ══════════════════════════════════════════════════════════════ */
        .party-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0 10px 0;
        }
        .party-table td {
            padding: 2px 4px;
            vertical-align: top;
            font-size: 9pt;
        }
        .party-label {
            width: 28%;
            color: #4b5563;
        }
        .party-colon {
            width: 3%;
            color: #4b5563;
        }
        .party-val {
            width: 69%;
            font-weight: 600;
            color: #111827;
        }

        /* ══════════════════════════════════════════════════════════════ */
        /* PASAL-PASAL HEADINGS & TEXT                                   */
        /* ══════════════════════════════════════════════════════════════ */
        .pasal-block {
            margin-top: 12px;
            page-break-inside: avoid;
        }
        .pasal-header {
            text-align: center;
            font-size: 9.5pt;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #f3f4f6;
            border-top: 1px solid #d1d5db;
            border-bottom: 1px solid #d1d5db;
            padding: 4px 6px;
            margin-bottom: 6px;
            color: #111827;
            letter-spacing: 0.3px;
        }
        p {
            margin: 4px 0 6px 0;
            text-align: justify;
            text-justify: inter-word;
            font-size: 9pt;
            line-height: 1.45;
        }
        ol, ul {
            margin: 4px 0 6px 0;
            padding-left: 18px;
        }
        li {
            margin-bottom: 3.5px;
            text-align: justify;
            text-justify: inter-word;
            font-size: 9pt;
            line-height: 1.45;
        }

        /* ══════════════════════════════════════════════════════════════ */
        /* TANDA TANGAN & PENGESAHAN                                     */
        /* ══════════════════════════════════════════════════════════════ */
        .signature-table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .signature-col {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 10px;
        }
        .sig-role {
            font-size: 9pt;
            font-weight: bold;
            color: #111827;
            text-transform: uppercase;
        }
        .sig-entity {
            font-size: 8.5pt;
            color: #374151;
            margin-top: 2px;
        }
        .sig-space {
            height: 70px;
            position: relative;
        }
        .materai-box {
            display: inline-block;
            border: 1px dashed #9ca3af;
            color: #6b7280;
            font-size: 7pt;
            padding: 6px 12px;
            margin-top: 14px;
            background-color: #f9fafb;
            text-transform: uppercase;
        }
        .sig-name {
            font-size: 9pt;
            font-weight: bold;
            text-decoration: underline;
            color: #111827;
            margin-top: 4px;
        }
        .sig-title {
            font-size: 8pt;
            color: #4b5563;
        }

        /* FOOTER NOTE */
        .footer-note {
            margin-top: 25px;
            padding-top: 8px;
            border-top: 0.5px solid #e5e7eb;
            font-size: 7.5pt;
            color: #9ca3af;
            text-align: center;
            font-style: italic;
        }
    </style>
</head>
<body>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- KOP SURAT RESMI (Identik & Konsisten dengan Cetak Pengajuan & Slip) -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <table class="header-table">
        <tr>
            <td class="logo-col">
                <div class="logo-container">
                    @if(!empty($company['logo_path']))
                        <img src="{{ $company['logo_path'] }}" class="logo-img">
                    @else
                        <span class="logo-fallback">LOGO</span>
                    @endif
                </div>
            </td>
            <td class="company-info">
                <div class="company-title">Surat Perjanjian Kerja</div>
                <div class="company-name">{{ $company['name'] }}</div>
                <div class="company-addr">{{ $company['address'] }}</div>
            </td>
            <td class="meta-info">
                <div class="ref-no">#{{ $karyawan->nomor_kontrak ?? ('PKWT/HRD/' . date('Y') . '/' . $karyawan->nomor_induk_karyawan) }}</div>
                <div class="meta-date">Tanggal: {{ \Carbon\Carbon::parse($karyawan->tgl_bergabung ?? now())->translatedFormat('d F Y') }}</div>
                <div class="status-badge">{{ strtoupper($karyawan->contract_type ?? 'PKWT') }}</div>
            </td>
        </tr>
    </table>

    <!-- JUDUL RESMI PERJANJIAN KERJA -->
    <div class="doc-title-wrapper">
        <div class="doc-title">
            {{ $judulSurat ?? ( 'SURAT PERJANJIAN KERJA ' . (($karyawan->contract_type == 'PKWTT') ? 'WAKTU TIDAK TERTENTU (PKWTT)' : 'WAKTU TERTENTU (PKWT)') ) }}
        </div>
        <div class="doc-sub">Nomor: {{ $karyawan->nomor_kontrak ?? ('PKWT/HRD/' . date('Y') . '/' . $karyawan->nomor_induk_karyawan) }}</div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- KONTEN DINAMIS DARI MASTER TEMPLATE / CUSTOM CONTRACT EDITOR       -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="contract-body">
        {!! $contractBody !!}
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- BLOK TANDA TANGAN PARA PIHAK & MATERAI                             -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <table class="signature-table">
        <tr>
            <td class="signature-col">
                <div class="sig-role">PIHAK PERTAMA</div>
                <div class="sig-entity">{{ $company['name'] }}</div>
                <div class="sig-space"></div>
                <div class="sig-name">( HRD / Manajemen Perseroan )</div>
                <div class="sig-title">Kuasa Direksi Pemberi Kerja</div>
            </td>
            <td class="signature-col">
                <div class="sig-role">PIHAK KEDUA</div>
                <div class="sig-entity">Karyawan / Pekerja Bersangkutan</div>
                <div class="sig-space">
                    <div class="materai-box">Materai Rp 10.000,-</div>
                </div>
                <div class="sig-name">( {{ strtoupper($karyawan->nama_lengkap) }} )</div>
                <div class="sig-title">NIK: #{{ $karyawan->nomor_induk_karyawan }}</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Dokumen ini diterbitkan secara sah dan tervalidasi melalui Sistem Enterprise Resource Planning (ERP) {{ $company['name'] }}.
    </div>

</body>
</html>
