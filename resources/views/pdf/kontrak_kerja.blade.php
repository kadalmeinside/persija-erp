<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Perjanjian Kerja - {{ $karyawan->nama_lengkap }}</title>
    <style>
        @page {
            margin: 1.5cm 2cm 2cm 2cm;
            footer: page-footer;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10.5pt;
            line-height: 1.45;
            color: #1a1a1a;
        }
        
        /* HEADER KOP RESMI (Standardized with Pengajuan Dana & Payslip) */
        .header-table {
            width: 100%;
            border-bottom: 2.5px solid #222222;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .logo-cell {
            width: 75px;
            vertical-align: middle;
        }
        .logo-container {
            width: 70px;
            height: 70px;
            border: 1px solid #e5e7eb;
            text-align: center;
            vertical-align: middle;
            background-color: #f9fafb;
        }
        .logo-img {
            max-width: 65px;
            max-height: 65px;
        }
        .logo-fallback {
            font-size: 8pt;
            font-weight: bold;
            color: #9ca3af;
            line-height: 70px;
        }
        .company-cell {
            padding-left: 15px;
            vertical-align: middle;
        }
        .company-name {
            font-size: 14pt;
            font-weight: 800;
            color: #111827;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin: 0;
        }
        .company-addr {
            font-size: 8.5pt;
            color: #4b5563;
            margin-top: 3px;
            line-height: 1.3;
            white-space: pre-line;
        }

        /* TITLE & HEADINGS */
        .title {
            text-align: center;
            margin-bottom: 18px;
        }
        .title h2 {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
            letter-spacing: 0.5px;
            text-decoration: underline;
        }
        .title p {
            font-size: 9.5pt;
            color: #374151;
            margin-top: 3px;
        }
        .section-title {
            font-size: 10pt;
            font-weight: bold;
            text-align: center;
            margin-top: 14px;
            margin-bottom: 6px;
            text-transform: uppercase;
            background-color: #f3f4f6;
            padding: 3px 0;
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
        }
        .table-data {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
        }
        .table-data td {
            padding: 2.5px 4px;
            vertical-align: top;
            font-size: 10pt;
        }
        .table-data td.label {
            width: 32%;
            color: #374151;
        }
        .table-data td.colon {
            width: 3%;
        }
        .table-data td.val {
            width: 65%;
            font-weight: 600;
        }
        ol, ul {
            margin-top: 3px;
            margin-bottom: 6px;
            padding-left: 18px;
        }
        li {
            margin-bottom: 3px;
            font-size: 10pt;
            text-align: justify;
        }
        p {
            margin: 5px 0;
            text-align: justify;
        }
        .signature-table {
            width: 100%;
            margin-top: 35px;
            page-break-inside: avoid;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
        }
        .signature-space {
            height: 65px;
        }
        .stamp-box {
            display: inline-block;
            border: 1px dashed #9ca3af;
            color: #9ca3af;
            font-size: 7.5pt;
            padding: 6px 10px;
            margin-top: 12px;
        }
    </style>
</head>
<body>

    <!-- KOP RESMI PERUSAHAAN (Standardized from Central Settings) -->
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <div class="logo-container">
                    @if(isset($company['logo_path']) && $company['logo_path'])
                        <img src="{{ $company['logo_path'] }}" class="logo-img">
                    @else
                        <span class="logo-fallback">LOGO</span>
                    @endif
                </div>
            </td>
            <td class="company-cell">
                <div class="company-name">{{ $company['name'] }}</div>
                <div class="company-addr">{{ $company['address'] }}</div>
            </td>
        </tr>
    </table>

    <!-- JUDUL SURAT -->
    <div class="title">
        <h2>SURAT PERJANJIAN KERJA ({{ strtoupper($karyawan->contract_type ?? 'PKWT') }})</h2>
        <p>Nomor: {{ $karyawan->nomor_kontrak ?? ('PKWT/HRD/' . date('Y') . '/' . $karyawan->nomor_induk_karyawan) }}</p>
    </div>

    <p>Pada hari ini, bertempat di Kantor {{ $company['name'] }}, telah dibuat dan disepakati perjanjian kerja bersama antara pihak-pihak sebagai berikut:</p>

    <!-- PIHAK PERTAMA (Pemberi Kerja) -->
    <table class="table-data">
        <tr>
            <td class="label">Nama Perusahaan</td>
            <td class="colon">:</td>
            <td class="val">{{ $company['name'] }}</td>
        </tr>
        <tr>
            <td class="label">Alamat Kantor</td>
            <td class="colon">:</td>
            <td class="val">{{ $company['address'] }}</td>
        </tr>
        <tr>
            <td class="label">Kedudukan</td>
            <td class="colon">:</td>
            <td class="val">Pemberi Kerja (disebut sebagai <strong>PIHAK PERTAMA</strong>)</td>
        </tr>
    </table>

    <p>Dan menerangkan bahwa:</p>

    <!-- PIHAK KEDUA (Pekerja) -->
    <table class="table-data">
        <tr>
            <td class="label">Nama Lengkap</td>
            <td class="colon">:</td>
            <td class="val">{{ $karyawan->nama_lengkap }}</td>
        </tr>
        <tr>
            <td class="label">Nomor Induk Karyawan (NIK)</td>
            <td class="colon">:</td>
            <td class="val">#{{ $karyawan->nomor_induk_karyawan }}</td>
        </tr>
        <tr>
            <td class="label">Tempat, Tanggal Lahir</td>
            <td class="colon">:</td>
            <td class="val">{{ $karyawan->tempat_lahir ?? '-' }}, {{ $karyawan->tgl_lahir ? date('d F Y', strtotime($karyawan->tgl_lahir)) : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Alamat Domisili</td>
            <td class="colon">:</td>
            <td class="val">{{ $karyawan->alamat ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Kedudukan</td>
            <td class="colon">:</td>
            <td class="val">Pekerja / Karyawan (disebut sebagai <strong>PIHAK KEDUA</strong>)</td>
        </tr>
    </table>

    <p>Kedua belah pihak secara sadar dan sukarela telah sepakat untuk mengikatkan diri dalam Perjanjian Kerja dengan syarat dan ketentuan yang diatur dalam pasal-pasal berikut:</p>

    <!-- PASAL 1 -->
    <div class="section-title">Pasal 1: Posisi, Penempatan & Jangka Waktu Kontrak</div>
    <ol>
        <li>PIHAK PERTAMA mempekerjakan PIHAK KEDUA dengan jabatan <strong>{{ $karyawan->jabatan }}</strong> pada Departemen <strong>{{ $karyawan->departemen->nama_departemen ?? 'Semua Departemen' }}</strong>.</li>
        <li>Hubungan kerja ini berlaku terhitung sejak tanggal <strong>{{ $karyawan->tgl_bergabung ? date('d F Y', strtotime($karyawan->tgl_bergabung)) : '-' }}</strong> sampai dengan tanggal <strong>{{ $karyawan->tanggal_berakhir_kontrak ? date('d F Y', strtotime($karyawan->tanggal_berakhir_kontrak)) : 'Selesai / Evaluasi Berkala' }}</strong>.</li>
        <li>Masa percobaan (probation) ditetapkan selama <strong>{{ $karyawan->durasi_probation_bulan ?? 3 }} bulan</strong>{{ $karyawan->tanggal_selesai_probation ? (' (berakhir pada ' . date('d F Y', strtotime($karyawan->tanggal_selesai_probation)) . ')') : '' }}.</li>
    </ol>

    <!-- PASAL 2 -->
    <div class="section-title">Pasal 2: Lingkup Tugas & Tanggung Jawab (Working Scope)</div>
    <p>PIHAK KEDUA berkewajiban melaksanakan tugas dan tanggung jawab profesional yang mencakup antara lain:</p>
    <ul>
        @if(!empty($karyawan->working_scope) && is_array($karyawan->working_scope))
            @foreach($karyawan->working_scope as $scope)
                <li>{{ $scope }}</li>
            @endforeach
        @else
            <li>Melaksanakan kewajiban kerja profesional secara penuh dan penuh dedikasi sesuai jabatan {{ $karyawan->jabatan }}.</li>
            <li>Berkoordinasi aktif dengan rekan kerja di bawah supervisi pimpinan departemen terkait.</li>
            <li>Menjaga integritas, etika profesi, dan mematuhi Standar Operasional Prosedur (SOP) internal {{ $company['name'] }}.</li>
            <li>Memelihara kerahasiaan seluruh dokumen, data strategis, dan aset milik perusahaan.</li>
        @endif
    </ul>

    <!-- PASAL 3 -->
    <div class="section-title">Pasal 3: Waktu Kerja & Lokasi Penugasan</div>
    <ol>
        <li>Waktu kerja standar adalah <strong>{{ $karyawan->jam_kerja_mingguan ?? 40 }} jam per minggu</strong>, disesuaikan dengan kebutuhan operasional dan jadwal penugasan.</li>
        <li>Lokasi penempatan kerja utama berada di <strong>{{ $karyawan->lokasiKantor->nama_kantor ?? 'Kantor Pusat / Bebas' }}</strong> dengan kebijakan presensi {{ $karyawan->is_strict_location ? 'STRICT (Wajib di radius kantor)' : 'ROAMING (Diizinkan lintas cabang/dinas)' }}.</li>
    </ol>

    <!-- PASAL 4 -->
    <div class="section-title">Pasal 4: Kompensasi, Tunjangan & Fasilitas</div>
    <ol>
        <li>PIHAK PERTAMA memberikan Gaji Pokok sebesar <strong>Rp {{ number_format($karyawan->gaji_pokok ?? 0, 0, ',', '.') }}</strong> per bulan kepada PIHAK KEDUA.</li>
        @if($karyawan->komponenGaji && count($karyawan->komponenGaji) > 0)
            <li>Tunjangan tetap/rutin tambahan terdiri dari:
                <ul>
                    @foreach($karyawan->komponenGaji as $komponen)
                        <li>{{ $komponen->komponenGaji->nama_komponen ?? 'Tunjangan' }}: Rp {{ number_format($komponen->nominal, 0, ',', '.') }} ({{ $komponen->keterangan ?? 'Rutin' }})</li>
                    @endforeach
                </ul>
            </li>
        @endif
        <li>Kompensasi dibayarkan setiap akhir periode bulan berjalan melalui transfer ke rekening bank PIHAK KEDUA ({{ $karyawan->primary_bank ? ($karyawan->primary_bank->nama_bank . ' - ' . $karyawan->primary_bank->nomor_rekening . ' a.n ' . $karyawan->primary_bank->atas_nama_rekening) : 'Rekening Karyawan Terdaftar' }}).</li>
        <li>PIHAK KEDUA didaftarkan dalam program BPJS Ketenagakerjaan ({{ $karyawan->no_bpjs_ketenagakerjaan ?? 'Dalam proses pendaftaran' }}) dan BPJS Kesehatan ({{ $karyawan->no_bpjs_kesehatan ?? 'Dalam proses pendaftaran' }}).</li>
    </ol>

    <!-- PASAL 5 -->
    <div class="section-title">Pasal 5: Kerahasiaan & Pengakhiran Hubungan Kerja</div>
    <ol>
        <li>PIHAK KEDUA dilarang keras membocorkan data strategi, data medis atlet, rahasia dagang/komersial, maupun informasi konfidensial perusahaan kepada pihak manapun tanpa izin tertulis.</li>
        <li>Apabila salah satu pihak berkehendak mengakhiri hubungan kerja sebelum berakhirnya jangka waktu perjanjian, wajib menyampaikan pemberitahuan tertulis (Notice Period) sekurang-kurangnya <strong>{{ $karyawan->notice_period_hari ?? 30 }} hari</strong> sebelumnya.</li>
    </ol>

    <!-- TANDA TANGAN KEDUA PIHAK -->
    <table class="signature-table">
        <tr>
            <td>
                <strong>PIHAK PERTAMA</strong><br>
                {{ $company['name'] }}<br>
                <div class="signature-space"></div>
                <strong>( Manajemen / HRD Manager )</strong>
            </td>
            <td>
                <strong>PIHAK KEDUA</strong><br>
                Karyawan Bersangkutan<br>
                <div class="stamp-box">Materai Rp 10.000</div>
                <div style="height: 12px;"></div>
                <strong>( {{ strtoupper($karyawan->nama_lengkap) }} )</strong>
            </td>
        </tr>
    </table>

</body>
</html>
