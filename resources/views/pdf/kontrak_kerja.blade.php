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
            SURAT PERJANJIAN KERJA {{ ($karyawan->contract_type == 'PKWTT') ? 'WAKTU TIDAK TERTENTU (PKWTT)' : 'WAKTU TERTENTU (PKWT)' }}
        </div>
        <div class="doc-sub">Nomor: {{ $karyawan->nomor_kontrak ?? ('PKWT/HRD/' . date('Y') . '/' . $karyawan->nomor_induk_karyawan) }}</div>
    </div>

    <!-- PREAMBLE / PEMBUKAAN -->
    <p>
        Pada hari ini, bertempat di kantor <strong>{{ $company['name'] }}</strong>, telah dibuat, disepakati, dan ditandatangani Perjanjian Kerja oleh dan antara pihak-pihak di bawah ini:
    </p>

    <!-- IDENTITAS PIHAK PERTAMA (Pemberi Kerja) -->
    <table class="party-table">
        <tr>
            <td class="party-label">Nama Badan Usaha</td>
            <td class="party-colon">:</td>
            <td class="party-val"><strong>{{ $company['name'] }}</strong></td>
        </tr>
        <tr>
            <td class="party-label">Alamat Kantor Resmi</td>
            <td class="party-colon">:</td>
            <td class="party-val">{{ $company['address'] }}</td>
        </tr>
        <tr>
            <td class="party-label">Diwakili Oleh</td>
            <td class="party-colon">:</td>
            <td class="party-val">Manajemen / Direksi yang berwenang bertindak untuk dan atas nama Perseroan</td>
        </tr>
        <tr>
            <td class="party-label">Kedudukan Hukum</td>
            <td class="party-colon">:</td>
            <td class="party-val">Pemberi Kerja (selanjutnya dalam Perjanjian ini disebut sebagai <strong>"PIHAK PERTAMA"</strong>)</td>
        </tr>
    </table>

    <p style="margin-top: 6px; margin-bottom: 2px;">Dan menerangkan bahwa:</p>

    <!-- IDENTITAS PIHAK KEDUA (Pekerja / Karyawan) -->
    <table class="party-table">
        <tr>
            <td class="party-label">Nama Lengkap</td>
            <td class="party-colon">:</td>
            <td class="party-val"><strong>{{ $karyawan->nama_lengkap }}</strong></td>
        </tr>
        <tr>
            <td class="party-label">Nomor Induk Karyawan (NIK)</td>
            <td class="party-colon">:</td>
            <td class="party-val">#{{ $karyawan->nomor_induk_karyawan }}</td>
        </tr>
        <tr>
            <td class="party-label">Tempat, Tanggal Lahir</td>
            <td class="party-colon">:</td>
            <td class="party-val">{{ $karyawan->tempat_lahir ?? '-' }}, {{ $karyawan->tgl_lahir ? \Carbon\Carbon::parse($karyawan->tgl_lahir)->translatedFormat('d F Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="party-label">Jenis Kelamin</td>
            <td class="party-colon">:</td>
            <td class="party-val">{{ $karyawan->jenis_kelamin == 'L' ? 'Laki-laki' : ($karyawan->jenis_kelamin == 'P' ? 'Perempuan' : '-') }}</td>
        </tr>
        <tr>
            <td class="party-label">Alamat Domisili</td>
            <td class="party-colon">:</td>
            <td class="party-val">{{ $karyawan->alamat ?? 'Sesuai KTP / Berkas Terdaftar' }}</td>
        </tr>
        <tr>
            <td class="party-label">Kedudukan Hukum</td>
            <td class="party-colon">:</td>
            <td class="party-val">Pekerja / Karyawan (selanjutnya dalam Perjanjian ini disebut sebagai <strong>"PIHAK KEDUA"</strong>)</td>
        </tr>
    </table>

    <p>
        PIHAK PERTAMA dan PIHAK KEDUA secara bersama-sama disebut sebagai <strong>"PARA PIHAK"</strong>, dan secara sendiri-sendiri disebut sebagai <strong>"PIHAK"</strong>. PARA PIHAK secara sadar, sukarela, dan tanpa paksaan sepakat mengikatkan diri dalam Perjanjian Kerja dengan ketentuan pasal-pasal sebagai berikut:
    </p>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- PASAL 1: POSISI, STATUS & PENEMPATAN KERJA                         -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="pasal-block">
        <div class="pasal-header">Pasal 1: Posisi, Status Hubungan Kerja & Penempatan</div>
        <ol>
            <li>PIHAK PERTAMA menerima dan mempekerjakan PIHAK KEDUA, dan PIHAK KEDUA sepakat untuk bekerja pada PIHAK PERTAMA dengan status hubungan kerja <strong>{{ strtoupper($karyawan->contract_type ?? 'PKWT') }}</strong>.</li>
            <li>PIHAK KEDUA memegang jabatan dan tugas sebagai <strong>{{ $karyawan->jabatan }}</strong> pada Departemen <strong>{{ $karyawan->departemen->nama_departemen ?? 'Operasional Klub' }}</strong>.</li>
            <li>Lokasi penempatan kerja utama PIHAK KEDUA bertempat di <strong>{{ $karyawan->lokasiKantor->nama_kantor ?? 'Kantor Pusat / Training Ground' }}</strong>. PIHAK PERTAMA berhak menugaskan PIHAK KEDUA ke lokasi cabang, venue pertandingan, atau penugasan dinas luar kota sesuai kebutuhan operasional.</li>
        </ol>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- PASAL 2: JANGKA WAKTU & EVALUASI / MASA PERCOBAAN                   -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="pasal-block">
        <div class="pasal-header">Pasal 2: Jangka Waktu Perjanjian & Ketentuan Evaluasi</div>
        @if(($karyawan->contract_type ?? 'PKWT') == 'PKWTT')
            <ol>
                <li>Hubungan kerja ini adalah Perjanjian Kerja Waktu Tidak Tertentu (PKWTT) yang berlaku efektif terhitung sejak tanggal <strong>{{ $karyawan->tgl_bergabung ? \Carbon\Carbon::parse($karyawan->tgl_bergabung)->translatedFormat('d F Y') : '-' }}</strong> hingga mencapai batas usia pensiun atau diakhirinya hubungan kerja sesuai peraturan perundang-undangan.</li>
                <li>Berdasarkan Pasal 60 UU Ketenagakerjaan No. 13 Tahun 2003, PIHAK KEDUA menjalani masa percobaan (probation) paling lama <strong>{{ $karyawan->durasi_probation_bulan ?? 3 }} bulan</strong>{{ $karyawan->tanggal_selesai_probation ? ' (sampai dengan tanggal ' . \Carbon\Carbon::parse($karyawan->tanggal_selesai_probation)->translatedFormat('d F Y') . ')' : '' }}.</li>
                <li>Selama masa percobaan, masing-masing pihak dapat mengakhiri hubungan kerja sewaktu-waktu dengan pemberitahuan tertulis. Apabila dinyatakan lulus masa percobaan, PIHAK PERTAMA akan menerbitkan Surat Keputusan (SK) Pengangkatan Karyawan Tetap.</li>
            </ol>
        @else
            <ol>
                <li>Perjanjian Kerja Waktu Tertentu (PKWT) ini berlaku untuk jangka waktu terhitung sejak tanggal <strong>{{ $karyawan->tgl_bergabung ? \Carbon\Carbon::parse($karyawan->tgl_bergabung)->translatedFormat('d F Y') : '-' }}</strong> sampai dengan tanggal <strong>{{ $karyawan->tanggal_berakhir_kontrak ? \Carbon\Carbon::parse($karyawan->tanggal_berakhir_kontrak)->translatedFormat('d F Y') : 'Selesai Periode Penugasan' }}</strong>.</li>
                <li>Sesuai dengan ketentuan Pasal 58 Undang-Undang Ketenagakerjaan No. 13 Tahun 2003, dalam Perjanjian Kerja Waktu Tertentu tidak disyaratkan adanya masa percobaan kerja.</li>
                <li>PIHAK PERTAMA akan melakukan <strong>Evaluasi Kinerja Berkala</strong> (setiap {{ $karyawan->durasi_probation_bulan ?? 3 }} bulan) terhadap pencapaian target kerja dan Key Performance Indicators (KPI) PIHAK KEDUA.</li>
                <li>Perpanjangan atau pembaruan jangka waktu perjanjian kerja hanya dapat dilakukan atas kesepakatan tertulis PARA PIHAK dengan mengacu pada ketentuan Peraturan Pemerintah No. 35 Tahun 2021.</li>
            </ol>
        @endif
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- PASAL 3: RUANG LINGKUP TUGAS (WORKING SCOPE)                       -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="pasal-block">
        <div class="pasal-header">Pasal 3: Ruang Lingkup Tugas & Tanggung Jawab (Working Scope)</div>
        <p>PIHAK KEDUA berkewajiban mendedikasikan waktu, tenaga, keahlian, dan integritas kerja secara penuh untuk melaksanakan tugas-tugas pokok yang meliputi:</p>
        <ul>
            @if(!empty($karyawan->working_scope) && is_array($karyawan->working_scope) && count($karyawan->working_scope) > 0)
                @foreach($karyawan->working_scope as $scope)
                    <li>{{ $scope }}</li>
                @endforeach
            @else
                <li>Melaksanakan tugas operasional profesional sesuai uraian jabatan <strong>{{ $karyawan->jabatan }}</strong> dengan standar mutu tertinggi.</li>
                <li>Berkoordinasi aktif di bawah supervisi Kepala Departemen <strong>{{ $karyawan->departemen->nama_departemen ?? 'terkait' }}</strong>.</li>
                <li>Mematuhi Standar Operasional Prosedur (SOP), kode etik klub/perusahaan, dan peraturan tata tertib kerja internal.</li>
                <li>Menjaga integritas, disiplin, dan kerahasiaan seluruh aset, inventaris, dan informasi milik PIHAK PERTAMA.</li>
            @endif
        </ul>
        <p>Selain tugas pokok di atas, PIHAK KEDUA bersedia menjalankan tugas kedinasan wajar lainnya yang diinstruksikan oleh atasan atau pimpinan manajemen demi kelancaran operasional perusahaan.</p>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- PASAL 4: WAKTU KERJA, PRESENSI & LEMBUR                            -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="pasal-block">
        <div class="pasal-header">Pasal 4: Waktu Kerja, Kehadiran & Lembur</div>
        <ol>
            <li>Waktu kerja standar adalah <strong>{{ $karyawan->jam_kerja_mingguan ?? 40 }} jam per minggu</strong>, yang diatur sesuai jadwal operasional kantor atau jadwal penugasan pertandingan.</li>
            <li>PIHAK KEDUA wajib melakukan pencatatan kehadiran (check-in & check-out) melalui sistem ERP Presensi dengan kebijakan lokasi: <strong>{{ $karyawan->is_strict_location ? 'STRICT (Wajib presensi di radius kantor/venue resmi)' : 'ROAMING (Diizinkan presensi tugas dinas lintas cabang/lapangan)' }}</strong>.</li>
            <li>Apabila PIHAK KEDUA diperintahkan bekerja melebihi waktu kerja normal (lembur) atas instruksi tertulis atasan, perhitungan kompensasi lembur mengacu pada ketentuan Peraturan Pemerintah No. 35 Tahun 2021 dan peraturan internal perusahaan.</li>
        </ol>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- PASAL 5: KOMPENSASI, UPAH & TUNJANGAN                              -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="pasal-block">
        <div class="pasal-header">Pasal 5: Kompensasi, Upah & Tunjangan</div>
        <ol>
            <li>PIHAK PERTAMA memberikan upah bulanan kepada PIHAK KEDUA dengan rincian:
                <ul>
                    <li><strong>Gaji Pokok</strong>: Sebesar <strong>Rp {{ number_format($karyawan->gaji_pokok ?? 0, 0, ',', '.') }}</strong> per bulan.</li>
                    @if($karyawan->komponenGaji && count($karyawan->komponenGaji) > 0)
                        @foreach($karyawan->komponenGaji as $komponen)
                            <li>{{ $komponen->komponenGaji->nama_komponen ?? 'Tunjangan Rutin' }}: Rp {{ number_format($komponen->nominal, 0, ',', '.') }} ({{ $komponen->keterangan ?? 'Rutin Per Bulan' }})</li>
                        @endforeach
                    @endif
                </ul>
            </li>
            <li>Pembayaran upah dilakukan setiap akhir periode bulan berjalan (cut-off payroll) melalui transfer ke rekening bank resmi PIHAK KEDUA:
                <br><strong>Bank: {{ $karyawan->primary_bank->nama_bank ?? 'Rekening Karyawan Terdaftar' }}</strong> | 
                <strong>No. Rek: {{ $karyawan->primary_bank->nomor_rekening ?? '-' }}</strong> | 
                <strong>a.n: {{ $karyawan->primary_bank->atas_nama_rekening ?? $karyawan->nama_lengkap }}</strong>.
            </li>
            <li>PIHAK PERTAMA memotong Pajak Penghasilan (PPh Pasal 21) sesuai ketentuan peraturan perpajakan yang berlaku dengan status PTKP (<strong>{{ $karyawan->status_ptkp ?? 'TK/0' }}</strong>) serta iuran kepesertaan jaminan sosial ketenagakerjaan dan kesehatan.</li>
        </ol>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- PASAL 6: JAMINAN SOSIAL & KESEHATAN (BPJS)                         -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="pasal-block">
        <div class="pasal-header">Pasal 6: Jaminan Sosial & Kesehatan</div>
        <ol>
            <li>PIHAK KEDUA didaftarkan dalam program perlindungan Jaminan Sosial Ketenagakerjaan (BPJS Ketenagakerjaan No: <strong>{{ $karyawan->no_bpjs_ketenagakerjaan ?? 'Dalam proses pendaftaran' }}</strong>) yang mencakup JKK, JKM, JHT, dan JP sesuai perundang-undangan.</li>
            <li>PIHAK KEDUA didaftarkan dalam program Jaminan Kesehatan Nasional (BPJS Kesehatan No: <strong>{{ $karyawan->no_bpjs_kesehatan ?? 'Dalam proses pendaftaran' }}</strong>).</li>
            <li>Iuran jaminan sosial ditanggung bersama antara PIHAK PERTAMA dan PIHAK KEDUA dengan formula dan persentase potongan resmi dari pemerintah.</li>
        </ol>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- PASAL 7: UANG KOMPENSASI PKWT (PP No. 35 TAHUN 2021)               -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    @if(($karyawan->contract_type ?? 'PKWT') != 'PKWTT')
    <div class="pasal-block">
        <div class="pasal-header">Pasal 7: Hak Uang Kompensasi Berakhirnya Kontrak (PP No. 35/2021)</div>
        <ol>
            <li>Berdasarkan Pasal 15 sampai dengan Pasal 17 Peraturan Pemerintah No. 35 Tahun 2021, pada saat berakhirnya jangka waktu PKWT, PIHAK PERTAMA wajib memberikan <strong>Uang Kompensasi</strong> kepada PIHAK KEDUA yang telah memiliki masa kerja paling sedikit 1 (satu) bulan secara terus-menerus.</li>
            <li>Besaran uang kompensasi dihitung secara proporsional sesuai ketentuan hukum:
                <ul>
                    <li>Masa kerja 12 (dua belas) bulan terus-menerus diberikan sebesar 1 (satu) bulan upah.</li>
                    <li>Masa kerja kurang dari atau lebih dari 12 bulan dihitung proporsional: <code>(Masa Kerja / 12) x 1 Bulan Upah Pokok</code>.</li>
                </ul>
            </li>
            <li>Hak uang kompensasi tidak berlaku jika pengakhiran hubungan kerja dilakukan atas pengunduran diri sukarela dari PIHAK KEDUA sebelum batas waktu berakhir, atau akibat sanksi pemutusan hubungan kerja atas pelanggaran berat.</li>
        </ol>
    </div>
    @endif

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- PASAL 8: HAK & KEWAJIBAN PARA PIHAK                                -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="pasal-block">
        <div class="pasal-header">Pasal 8: Hak & Kewajiban Para Pihak</div>
        <ol>
            <li><strong>Hak & Kewajiban PIHAK PERTAMA:</strong>
                <ul>
                    <li>Berhak atas hasil kerja, loyalitas, dedikasi waktu, dan kepatuhan dari PIHAK KEDUA.</li>
                    <li>Berhak melakukan evaluasi berkala, pengawasan, dan penetapan kebijakan operasional.</li>
                    <li>Berkewajiban membayarkan kompensasi, upah, tunjangan, dan iuran jaminan sosial secara tepat waktu.</li>
                    <li>Berkewajiban menyediakan sarana kerja dan menjamin standar Keselamatan dan Kesehatan Kerja (K3).</li>
                </ul>
            </li>
            <li><strong>Hak & Kewajiban PIHAK KEDUA:</strong>
                <ul>
                    <li>Berhak menerima upah, fasilitas kerja, dan istirahat/cuti tahunan sesuai ketentuan yang berlaku.</li>
                    <li>Berkewajiban menjalankan seluruh kewajiban kerja dengan itikad baik, jujur, tertib, dan teliti.</li>
                    <li>Berkewajiban menjaga nama baik, reputasi, dan kehormatan klub/perusahaan di hadapan publik maupun media sosial.</li>
                    <li>Berkewajiban merawat dan menjaga seluruh aset, perangkat keras, dan inventaris milik PIHAK PERTAMA.</li>
                </ul>
            </li>
        </ol>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- PASAL 9: KERAHASIAAN INFORMASI & HAK KEKAYAAN INTELEKTUAL          -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="pasal-block">
        <div class="pasal-header">Pasal 9: Kerahasiaan (NDA) & Hak Kekayaan Intelektual (HAKI)</div>
        <ol>
            <li>PIHAK KEDUA dilarang keras membocorkan data strategi tim/klub, data medis atlet/staf, rahasia komersial, kontrak pemain/ofisial, maupun source code dan data sistem ERP kepada pihak ketiga tanpa persetujuan tertulis dari PIHAK PERTAMA.</li>
            <li>Kewajiban menjaga kerahasiaan ini tetap mengikat PIHAK KEDUA secara hukum tanpa batas waktu atau sekurang-kurangnya 3 (tiga) tahun setelah berakhirnya hubungan kerja.</li>
            <li>Seluruh karya cipta, dokumen teknis, sistem analisis, atau program yang dibuat oleh PIHAK KEDUA dalam ruang lingkup kerja merupakan Hak Kekayaan Intelektual eksklusif milik PIHAK PERTAMA.</li>
        </ol>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- PASAL 10: TATA TERTIB, SANKSI & SURAT PERINGATAN                   -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="pasal-block">
        <div class="pasal-header">Pasal 10: Tata Tertib, Sanksi Disipliner & SP</div>
        <ol>
            <li>Pelanggaran terhadap disiplin dan SOP kerja akan dikenakan tindakan pembinaan bertahap melalui penerbitan Surat Peringatan Pertama (SP I), Surat Peringatan Kedua (SP II), atau Surat Peringatan Ketiga (SP III / Terakhir), yang masing-masing berlaku selama 6 (enam) bulan.</li>
            <li>PIHAK PERTAMA berhak langsung melakukan Pemutusan Hubungan Kerja (PHK) seketika tanpa peringatan dalam hal PIHAK KEDUA melakukan pelanggaran berat, seperti: tindak pidana, penipuan, penggelapan aset perusahaan, suap/pengaturan skor (match fixing), penyalahgunaan narkoba/miras di lingkungan kerja, atau kekerasan fisik.</li>
        </ol>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- PASAL 11: PENGAKHIRAN HUBUNGAN KERJA & NOTICE PERIOD               -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="pasal-block">
        <div class="pasal-header">Pasal 11: Pengakhiran Hubungan Kerja & Notice Period</div>
        <ol>
            <li>Hubungan kerja berakhir apabila jangka waktu perjanjian berakhir, pekerja meninggal dunia, atau adanya penetapan hukum yang berkekuatan tetap.</li>
            <li>Apabila PIHAK KEDUA berkehendak mengundurkan diri (resign) atas kemauan sendiri sebelum masa kerja berakhir, PIHAK KEDUA wajib menyampaikan surat pemberitahuan tertulis sekurang-kurangnya <strong>{{ $karyawan->notice_period_hari ?? 30 }} hari kalender</strong> sebelumnya (Notice Period).</li>
            <li>PIHAK KEDUA wajib melakukan serah terima pekerjaan (Handover) secara lengkap dan mengembalikan seluruh aset/perlengkapan kerja perusahaan (Exit Clearance) sebelum tanggal efektif pengunduran diri.</li>
        </ol>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- PASAL 12: PENYELESAIAN PERSELISIHAN                                -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="pasal-block">
        <div class="pasal-header">Pasal 12: Penyelesaian Perselisihan (Dispute Resolution)</div>
        <ol>
            <li>Segala perselisihan yang timbul sehubungan dengan pelaksanaan Perjanjian Kerja ini diselesaikan terlebih dahulu secara musyawarah mufakat melalui perundingan Bipartit.</li>
            <li>Apabila perundingan bipartit tidak mencapai kesepakatan dalam waktu 30 (tiga puluh) hari, penyelesaian akan ditempuh melalui mediasi pada Dinas Ketenagakerjaan setempat dan/atau melalui Pengadilan Hubungan Industrial (PHI) sesuai regulasi Republik Indonesia.</li>
        </ol>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- PASAL 13: KETENTUAN PENUTUP & PENGESAHAN                           -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="pasal-block">
        <div class="pasal-header">Pasal 13: Ketentuan Penutup</div>
        <ol>
            <li>Perjanjian Kerja ini dibuat dan ditafsirkan berdasarkan ketentuan hukum ketenagakerjaan yang berlaku di Negara Kesatuan Republik Indonesia.</li>
            <li>Hal-hal yang belum diatur atau perubahan atas syarat kerja akan dituangkan dalam Perjanjian Tambahan (Addendum) tertulis yang disepakati oleh PARA PIHAK.</li>
            <li>Perjanjian ini dibuat dalam rangkap 2 (dua) asli bermaterai cukup, ditandatangani oleh PARA PIHAK dalam keadaan sehat walafiat, sadar, dan masing-masing mempunyai kekuatan pembuktian hukum yang sama.</li>
        </ol>
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
