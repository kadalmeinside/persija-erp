<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Perjanjian Kerja - {{ $karyawan->nama_lengkap }}</title>
    <style>
        @page {
            margin: 1.8cm 2cm 2cm 2cm;
            footer: page-footer;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #1a1a1a;
        }
        .header {
            text-align: center;
            border-bottom: 2.5px solid #c91f1f;
            padding-bottom: 12px;
            margin-bottom: 24px;
        }
        .club-name {
            font-size: 16pt;
            font-weight: 800;
            color: #b91c1c;
            letter-spacing: 1px;
            margin: 0;
            text-transform: uppercase;
        }
        .company-sub {
            font-size: 9pt;
            color: #4b5563;
            margin-top: 3px;
        }
        .title {
            text-align: center;
            margin-bottom: 20px;
        }
        .title h2 {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
            letter-spacing: 0.5px;
            text-decoration: underline;
        }
        .title p {
            font-size: 10pt;
            color: #374151;
            margin-top: 4px;
        }
        .section-title {
            font-size: 11pt;
            font-weight: bold;
            text-align: center;
            margin-top: 16px;
            margin-bottom: 6px;
            text-transform: uppercase;
            background-color: #f3f4f6;
            padding: 3px 0;
        }
        .table-data {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }
        .table-data td {
            padding: 3px 4px;
            vertical-align: top;
            font-size: 10.5pt;
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
            margin-top: 4px;
            margin-bottom: 8px;
            padding-left: 20px;
        }
        li {
            margin-bottom: 4px;
            font-size: 10.5pt;
            text-align: justify;
        }
        p {
            margin: 6px 0;
            text-align: justify;
        }
        .signature-table {
            width: 100%;
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
        }
        .signature-space {
            height: 70px;
        }
        .stamp-box {
            display: inline-block;
            border: 1px dashed #9ca3af;
            color: #9ca3af;
            font-size: 8pt;
            padding: 8px 12px;
            margin-top: 15px;
        }
    </style>
</head>
<body>

    <!-- KOP RESMI -->
    <div class="header">
        <h1 class="club-name">PT PERSIJA JAYA JAKARTA</h1>
        <p class="company-sub">Rasuna Office Park, Jl. HR Rasuna Said, Kuningan, Jakarta Selatan • Website: persija.id</p>
    </div>

    <!-- JUDUL SURAT -->
    <div class="title">
        <h2>SURAT PERJANJIAN KERJA ({{ strtoupper($karyawan->contract_type ?? 'PKWT') }})</h2>
        <p>Nomor: {{ $karyawan->nomor_kontrak ?? ('PKWT/PJJ/' . date('Y') . '/' . $karyawan->nomor_induk_karyawan) }}</p>
    </div>

    <p>Pada hari ini, bertempat di Kantor PT Persija Jaya Jakarta, telah dibuat dan disepakati perjanjian kerja antara pihak-pihak sebagai berikut:</p>

    <!-- PIHAK PERTAMA -->
    <table class="table-data">
        <tr>
            <td class="label">Nama Perusahaan</td>
            <td class="colon">:</td>
            <td class="val">PT PERSIJA JAYA JAKARTA</td>
        </tr>
        <tr>
            <td class="label">Alamat Kantor</td>
            <td class="colon">:</td>
            <td class="val">Jakarta Selatan, DKI Jakarta</td>
        </tr>
        <tr>
            <td class="label">Kedudukan</td>
            <td class="colon">:</td>
            <td class="val">Pemberi Kerja (disebut sebagai <strong>PIHAK PERTAMA</strong>)</td>
        </tr>
    </table>

    <p>Dan menerangkan bahwa:</p>

    <!-- PIHAK KEDUA -->
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

    <p>Kedua belah pihak secara sadar dan sukarela telah sepakat untuk mengikatkan diri dalam Perjanjian Kerja dengan ketentuan dan syarat-syarat yang tercantum dalam pasal-pasal berikut:</p>

    <!-- PASAL 1 -->
    <div class="section-title">Pasal 1: Posisi, Departemen & Jangka Waktu Kontrak</div>
    <ol>
        <li>PIHAK PERTAMA menerima PIHAK KEDUA sebagai karyawan dengan jabatan <strong>{{ $karyawan->jabatan }}</strong> pada Departemen <strong>{{ $karyawan->departemen->nama_departemen ?? 'Semua Departemen' }}</strong>.</li>
        <li>Hubungan kerja ini berlaku terhitung sejak tanggal <strong>{{ $karyawan->tgl_bergabung ? date('d F Y', strtotime($karyawan->tgl_bergabung)) : '-' }}</strong> sampai dengan tanggal <strong>{{ $karyawan->tanggal_berakhir_kontrak ? date('d F Y', strtotime($karyawan->tanggal_berakhir_kontrak)) : 'Selesai / Evaluasi Berkala' }}</strong>.</li>
        <li>Masa percobaan (probation) ditetapkan selama <strong>{{ $karyawan->durasi_probation_bulan ?? 3 }} (tiga) bulan</strong>.</li>
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
            <li>Menjaga integritas, etika profesi, dan mematuhi Standar Operasional Prosedur (SOP) internal PT Persija Jaya Jakarta.</li>
            <li>Memelihara kerahasiaan seluruh dokumen, data taktis, dan aset milik klub.</li>
        @endif
    </ul>

    <!-- PASAL 3 -->
    <div class="section-title">Pasal 3: Waktu Kerja & Lokasi Penugasan</div>
    <ol>
        <li>Waktu kerja standar adalah <strong>{{ $karyawan->jam_kerja_mingguan ?? 40 }} jam per minggu</strong>, disesuaikan dengan kebutuhan operasional dan jadwal kompetisi klub.</li>
        <li>Lokasi penempatan kerja utama berada di <strong>{{ $karyawan->lokasiKantor->nama_kantor ?? 'Kantor Pusat / Bebas' }}</strong> dengan kebijakan presensi {{ $karyawan->is_strict_location ? 'STRICT (Wajib di radius kantor)' : 'ROAMING (Diizinkan lintas cabang/dinas)' }}.</li>
    </ol>

    <!-- PASAL 4 -->
    <div class="section-title">Pasal 4: Kompensasi & Tunjangan</div>
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
        <li>PIHAK KEDUA dilarang keras membocorkan data strategi, medis atlet, komersial sponsor, maupun informasi rahasia klub kepada pihak manapun.</li>
        <li>Apabila salah satu pihak berkehendak mengakhiri hubungan kerja sebelum berakhirnya jangka waktu perjanjian, wajib menyampaikan pemberitahuan tertulis (Notice Period) sekurang-kurangnya <strong>{{ $karyawan->notice_period_hari ?? 30 }} (tiga puluh) hari</strong> sebelumnya.</li>
    </ol>

    <!-- TANDA TANGAN -->
    <table class="signature-table">
        <tr>
            <td>
                <strong>PIHAK PERTAMA</strong><br>
                PT PERSIJA JAYA JAKARTA<br>
                <div class="signature-space"></div>
                <strong>( Direktur / HRD Manager )</strong>
            </td>
            <td>
                <strong>PIHAK KEDUA</strong><br>
                Karyawan Bersangkutan<br>
                <div class="stamp-box">Materai Rp 10.000</div>
                <div style="height: 15px;"></div>
                <strong>( {{ strtoupper($karyawan->nama_lengkap) }} )</strong>
            </td>
        </tr>
    </table>

</body>
</html>
