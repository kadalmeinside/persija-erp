<?php

namespace Database\Seeders;

use App\Models\ContractTemplate;
use Illuminate\Database\Seeder;

class ContractTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // 1. TEMPLATE PKWT (Perjanjian Kerja Waktu Tertentu)
        $pkwtContent = <<<'HTML'
<p>Pada hari ini, <strong>{TANGGAL_HARI_INI}</strong>, bertempat di kantor <strong>{COMPANY_NAME}</strong>, telah dibuat dan disepakati Perjanjian Kerja Waktu Tertentu (PKWT) antara pihak-pihak sebagai berikut:</p>

<table class="party-table">
    <tr>
        <td class="party-label">Nama Perusahaan</td>
        <td class="party-colon">:</td>
        <td class="party-val"><strong>{COMPANY_NAME}</strong></td>
    </tr>
    <tr>
        <td class="party-label">Alamat Kantor</td>
        <td class="party-colon">:</td>
        <td class="party-val">{COMPANY_ADDRESS}</td>
    </tr>
    <tr>
        <td class="party-label">Diwakili Oleh</td>
        <td class="party-colon">:</td>
        <td class="party-val">Direksi / Manajemen HRD yang berwenang bertindak untuk dan atas nama Perseroan</td>
    </tr>
    <tr>
        <td class="party-label">Kedudukan</td>
        <td class="party-colon">:</td>
        <td class="party-val">Pemberi Kerja (disebut sebagai <strong>"PIHAK PERTAMA"</strong>)</td>
    </tr>
</table>

<p style="margin-top: 6px; margin-bottom: 2px;">Dan menerangkan identitas pekerja:</p>

<table class="party-table">
    <tr>
        <td class="party-label">Nama Lengkap</td>
        <td class="party-colon">:</td>
        <td class="party-val"><strong>{NAMA_KARYAWAN}</strong></td>
    </tr>
    <tr>
        <td class="party-label">Nomor Induk Karyawan (NIK)</td>
        <td class="party-colon">:</td>
        <td class="party-val">#{NIK_KARYAWAN}</td>
    </tr>
    <tr>
        <td class="party-label">Tempat, Tanggal Lahir</td>
        <td class="party-colon">:</td>
        <td class="party-val">{TEMPAT_LAHIR}, {TANGGAL_LAHIR}</td>
    </tr>
    <tr>
        <td class="party-label">Jenis Kelamin</td>
        <td class="party-colon">:</td>
        <td class="party-val">{JENIS_KELAMIN}</td>
    </tr>
    <tr>
        <td class="party-label">Alamat Domisili</td>
        <td class="party-colon">:</td>
        <td class="party-val">{ALAMAT_KARYAWAN}</td>
    </tr>
    <tr>
        <td class="party-label">Kedudukan</td>
        <td class="party-colon">:</td>
        <td class="party-val">Pekerja / Karyawan (disebut sebagai <strong>"PIHAK KEDUA"</strong>)</td>
    </tr>
</table>

<p>PARA PIHAK secara sadar, sukarela, dan tanpa paksaan sepakat untuk mengikatkan diri dalam Perjanjian Kerja dengan syarat dan ketentuan yang diatur dalam pasal-pasal berikut:</p>

<div class="pasal-block">
    <div class="pasal-header">Pasal 1: Posisi, Status Hubungan Kerja & Penempatan</div>
    <ol>
        <li>PIHAK PERTAMA mempekerjakan PIHAK KEDUA dan PIHAK KEDUA sepakat bekerja pada PIHAK PERTAMA dengan status <strong>PKWT (Perjanjian Kerja Waktu Tertentu)</strong>.</li>
        <li>PIHAK KEDUA memangku jabatan sebagai <strong>{JABATAN}</strong> pada Departemen <strong>{DEPARTEMEN}</strong>.</li>
        <li>Lokasi penempatan kerja utama berada di <strong>{LOKASI_KANTOR}</strong>. PIHAK PERTAMA berhak menugaskan PIHAK KEDUA ke lokasi cabang, venue pertandingan, atau penugasan dinas luar kota sesuai kebutuhan operasional.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 2: Jangka Waktu Perjanjian & Evaluasi Kinerja Berkala</div>
    <ol>
        <li>Perjanjian Kerja ini berlaku terhitung sejak tanggal <strong>{TANGGAL_BERGABUNG}</strong> sampai dengan tanggal <strong>{TANGGAL_BERAKHIR}</strong>.</li>
        <li>Sesuai ketentuan Pasal 58 Undang-Undang Ketenagakerjaan No. 13 Tahun 2003, dalam Perjanjian Kerja Waktu Tertentu tidak disyaratkan adanya masa percobaan kerja.</li>
        <li>PIHAK PERTAMA akan menyelenggarakan <strong>Evaluasi Kinerja Berkala (Performance Appraisal)</strong> setiap <strong>{DURASI_PROBATION} bulan</strong> terhadap pencapaian target kerja dan Key Performance Indicators (KPI) PIHAK KEDUA.</li>
        <li>Perpanjangan perjanjian kerja hanya dapat dilakukan atas kesepakatan tertulis PARA PIHAK mengacu pada ketentuan Peraturan Pemerintah No. 35 Tahun 2021.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 3: Ruang Lingkup Tugas & Tanggung Jawab (Working Scope)</div>
    <p>PIHAK KEDUA berkewajiban mendedikasikan waktu, tenaga, keahlian, dan integritas kerja secara penuh untuk melaksanakan tugas-tugas pokok yang mencakup:</p>
    {WORKING_SCOPE}
    <p>Selain tugas pokok di atas, PIHAK KEDUA bersedia menjalankan tugas kedinasan wajar lainnya yang diinstruksikan oleh atasan atau pimpinan manajemen demi kelancaran operasional perusahaan.</p>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 4: Waktu Kerja, Kehadiran & Lembur</div>
    <ol>
        <li>Waktu kerja standar adalah <strong>{JAM_KERJA_MINGGUAN} jam per minggu</strong>, yang diatur sesuai kebutuhan jadwal operasional atau pertandingan.</li>
        <li>PIHAK KEDUA wajib mencatatkan kehadiran secara tertib melalui sistem ERP Presensi.</li>
        <li>Pekerjaan lembur yang melebihi jam kerja normal dilaksanakan atas persetujuan tertulis atasan dan diperhitungkan sesuai Peraturan Pemerintah No. 35 Tahun 2021.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 5: Kompensasi, Upah & Tunjangan</div>
    <ol>
        <li>PIHAK PERTAMA memberikan upah bulanan kepada PIHAK KEDUA berupa:
            <ul>
                <li><strong>Gaji Pokok</strong>: Sebesar <strong>{GAJI_POKOK}</strong> per bulan.</li>
                <li><strong>Tunjangan Tambahan</strong>: {TUNJANGAN_RUTIN}</li>
            </ul>
        </li>
        <li>Pembayaran upah dilakukan setiap akhir periode bulan berjalan melalui transfer ke rekening resmi PIHAK KEDUA ({REKENING_BANK}).</li>
        <li>Pemotongan Pajak Penghasilan (PPh Pasal 21) dikenakan sesuai status PTKP ({STATUS_PTKP}) serta iuran jaminan sosial ketenagakerjaan dan kesehatan.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 6: Jaminan Sosial & Kesehatan (BPJS)</div>
    <ol>
        <li>PIHAK KEDUA didaftarkan dalam program BPJS Ketenagakerjaan (No: <strong>{NO_BPJS_KETENAGAKERJAAN}</strong>) mencakup program JKK, JKM, JHT, dan JP.</li>
        <li>PIHAK KEDUA didaftarkan dalam program BPJS Kesehatan (No: <strong>{NO_BPJS_KESEHATAN}</strong>).</li>
        <li>Iuran jaminan sosial ditanggung bersama sesuai formula dan regulasi pemerintah yang berlaku.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 7: Hak Uang Kompensasi PKWT (PP No. 35 Tahun 2021)</div>
    <ol>
        <li>Sesuai Pasal 15 s.d. Pasal 17 PP No. 35 Tahun 2021, pada saat berakhirnya jangka waktu PKWT, PIHAK PERTAMA wajib memberikan <strong>Uang Kompensasi</strong> kepada PIHAK KEDUA yang telah bekerja minimal 1 (satu) bulan secara terus-menerus.</li>
        <li>Besaran kompensasi dihitung proporsional: <code>(Masa Kerja / 12) x 1 Bulan Upah Pokok</code>.</li>
        <li>Uang kompensasi tidak berlaku jika PIHAK KEDUA mengundurkan diri sukarela sebelum berakhirnya masa kontrak atau diberhentikan karena pelanggaran berat.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 8: Hak & Kewajiban Para Pihak</div>
    <ol>
        <li>PIHAK PERTAMA berhak atas hasil kerja dan kepatuhan dari PIHAK KEDUA, serta berkewajiban membayarkan kompensasi tepat waktu dan menjamin standar Keselamatan Kerja (K3).</li>
        <li>PIHAK KEDUA berhak menerima upah dan hak istirahat/cuti, serta berkewajiban menjaga nama baik, kehormatan, dan seluruh aset inventaris milik PIHAK PERTAMA.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 9: Kerahasiaan (NDA) & Hak Kekayaan Intelektual (HAKI)</div>
    <ol>
        <li>PIHAK KEDUA wajib menjaga kerahasiaan data strategi klub, data atlet, data medis, rahasia komersial, kontrak kerja, dan source code ERP perusahaan.</li>
        <li>Kewajiban kerahasiaan tetap mengikat PIHAK KEDUA secara hukum hingga 3 (tiga) tahun setelah berakhirnya hubungan kerja.</li>
        <li>Seluruh ciptaan atau karya yang dihasilkan selama hubungan kerja menjadi Hak Kekayaan Intelektual eksklusif milik PIHAK PERTAMA.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 10: Tata Tertib, Sanksi & Surat Peringatan</div>
    <ol>
        <li>Pelanggaran disiplin dikenakan tindakan pembinaan melalui Surat Peringatan (SP I, SP II, SP III) yang berlaku selama 6 (enam) bulan.</li>
        <li>PIHAK PERTAMA berhak melakukan Pemutusan Hubungan Kerja (PHK) seketika tanpa peringatan jika PIHAK KEDUA terbukti melakukan pelanggaran berat (tindak pidana, pencurian, narkoba, suap/match fixing).</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 11: Pengakhiran Hubungan Kerja & Notice Period</div>
    <ol>
        <li>Hubungan kerja berakhir apabila jangka waktu perjanjian berakhir atau pekerja meninggal dunia.</li>
        <li>Dalam hal PIHAK KEDUA mengundurkan diri (resign) sebelum jangka waktu berakhir, wajib menyampaikan pemberitahuan tertulis sekurang-kurangnya <strong>{NOTICE_PERIOD} hari kalender</strong> sebelumnya (Notice Period).</li>
        <li>PIHAK KEDUA wajib menyelesaikan serah terima tugas (handover) dan mengembalikan seluruh aset perusahaan (Exit Clearance).</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 12: Penyelesaian Perselisihan (Dispute Resolution)</div>
    <ol>
        <li>Segala perselisihan diselesaikan terlebih dahulu melalui musyawarah mufakat (Bipartit) dalam waktu maksimal 30 hari.</li>
        <li>Jika tidak tercapai kesepakatan, perselisihan diselesaikan melalui mediasi Dinas Ketenagakerjaan dan/atau Pengadilan Hubungan Industrial (PHI).</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 13: Ketentuan Penutup</div>
    <ol>
        <li>Perjanjian Kerja ini dibuat dan ditafsirkan berdasarkan hukum ketenagakerjaan Republik Indonesia.</li>
        <li>Dibuat rangkap 2 (dua) asli bermaterai cukup dan masing-masing memiliki kekuatan hukum yang sama.</li>
    </ol>
</div>
HTML;

        // 2. TEMPLATE PKWTT (Perjanjian Kerja Waktu Tidak Tertentu / Tetap)
        $pkwttContent = <<<'HTML'
<p>Pada hari ini, <strong>{TANGGAL_HARI_INI}</strong>, bertempat di kantor <strong>{COMPANY_NAME}</strong>, telah dibuat dan disepakati Perjanjian Kerja Waktu Tidak Tertentu (PKWTT / Karyawan Tetap) antara pihak-pihak sebagai berikut:</p>

<table class="party-table">
    <tr>
        <td class="party-label">Nama Perusahaan</td>
        <td class="party-colon">:</td>
        <td class="party-val"><strong>{COMPANY_NAME}</strong></td>
    </tr>
    <tr>
        <td class="party-label">Alamat Kantor</td>
        <td class="party-colon">:</td>
        <td class="party-val">{COMPANY_ADDRESS}</td>
    </tr>
    <tr>
        <td class="party-label">Diwakili Oleh</td>
        <td class="party-colon">:</td>
        <td class="party-val">Direksi / Manajemen HRD yang berwenang bertindak untuk dan atas nama Perseroan</td>
    </tr>
    <tr>
        <td class="party-label">Kedudukan</td>
        <td class="party-colon">:</td>
        <td class="party-val">Pemberi Kerja (disebut sebagai <strong>"PIHAK PERTAMA"</strong>)</td>
    </tr>
</table>

<p style="margin-top: 6px; margin-bottom: 2px;">Dan menerangkan identitas pekerja:</p>

<table class="party-table">
    <tr>
        <td class="party-label">Nama Lengkap</td>
        <td class="party-colon">:</td>
        <td class="party-val"><strong>{NAMA_KARYAWAN}</strong></td>
    </tr>
    <tr>
        <td class="party-label">Nomor Induk Karyawan (NIK)</td>
        <td class="party-colon">:</td>
        <td class="party-val">#{NIK_KARYAWAN}</td>
    </tr>
    <tr>
        <td class="party-label">Tempat, Tanggal Lahir</td>
        <td class="party-colon">:</td>
        <td class="party-val">{TEMPAT_LAHIR}, {TANGGAL_LAHIR}</td>
    </tr>
    <tr>
        <td class="party-label">Jenis Kelamin</td>
        <td class="party-colon">:</td>
        <td class="party-val">{JENIS_KELAMIN}</td>
    </tr>
    <tr>
        <td class="party-label">Alamat Domisili</td>
        <td class="party-colon">:</td>
        <td class="party-val">{ALAMAT_KARYAWAN}</td>
    </tr>
    <tr>
        <td class="party-label">Kedudukan</td>
        <td class="party-colon">:</td>
        <td class="party-val">Pekerja / Karyawan (disebut sebagai <strong>"PIHAK KEDUA"</strong>)</td>
    </tr>
</table>

<p>PARA PIHAK sepakat untuk mengikatkan diri dalam Perjanjian Kerja Waktu Tidak Tertentu dengan ketentuan pasal-pasal berikut:</p>

<div class="pasal-block">
    <div class="pasal-header">Pasal 1: Status Hubungan Kerja, Posisi & Penempatan</div>
    <ol>
        <li>PIHAK KEDUA bekerja pada PIHAK PERTAMA dengan status hubungan kerja <strong>PKWTT (Karyawan Tetap)</strong>.</li>
        <li>PIHAK KEDUA menduduki jabatan sebagai <strong>{JABATAN}</strong> pada Departemen <strong>{DEPARTEMEN}</strong>.</li>
        <li>Lokasi penempatan kerja utama di <strong>{LOKASI_KANTOR}</strong> dengan kewajiban siap ditugaskan dinas sesuai kebutuhan operasional.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 2: Masa Percobaan (Probation) & Pengangkatan</div>
    <ol>
        <li>Berdasarkan Pasal 60 UU Ketenagakerjaan No. 13 Tahun 2003, PIHAK KEDUA menjalani masa percobaan kerja paling lama <strong>{DURASI_PROBATION} bulan</strong> terhitung sejak {TANGGAL_BERGABUNG} sampai dengan {TANGGAL_SELESAI_PROBATION}.</li>
        <li>Selama masa percobaan, masing-masing pihak berhak mengakhiri hubungan kerja sewaktu-waktu dengan pemberitahuan tertulis tanpa ganti rugi.</li>
        <li>Apabila dinyatakan lulus evaluasi masa percobaan, PIHAK PERTAMA akan menerbitkan Surat Keputusan (SK) Pengangkatan Karyawan Tetap.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 3: Ruang Lingkup Tugas (Working Scope)</div>
    <p>PIHAK KEDUA berkewajiban menjalankan uraian tugas dan tanggung jawab profesional yang mencakup:</p>
    {WORKING_SCOPE}
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 4: Waktu Kerja & Kehadiran</div>
    <ol>
        <li>Waktu kerja standar adalah <strong>{JAM_KERJA_MINGGUAN} jam per minggu</strong>.</li>
        <li>PIHAK KEDUA wajib melakukan presensi kehadiran digital secara tepat waktu pada sistem ERP.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 5: Kompensasi, Upah & Rekening Bank</div>
    <ol>
        <li>Gaji pokok ditetapkan sebesar <strong>{GAJI_POKOK}</strong> per bulan.</li>
        <li>Tunjangan rutin: {TUNJANGAN_RUTIN}</li>
        <li>Pembayaran upah ditransfer setiap akhir periode bulan berjalan ke {REKENING_BANK}.</li>
        <li>Pajak penghasilan PPh 21 diperhitungkan berdasarkan status PTKP ({STATUS_PTKP}).</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 6: Jaminan Sosial & Kesehatan</div>
    <ol>
        <li>Peserta BPJS Ketenagakerjaan No: <strong>{NO_BPJS_KETENAGAKERJAAN}</strong>.</li>
        <li>Peserta BPJS Kesehatan No: <strong>{NO_BPJS_KESEHATAN}</strong>.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 7: Hak & Kewajiban Para Pihak</div>
    <ol>
        <li>PIHAK PERTAMA berkewajiban membayarkan upah, menyediakan sarana kerja, dan keselamatan kerja (K3).</li>
        <li>PIHAK KEDUA berkewajiban melaksanakan tugas dengan dedikasi tinggi serta menjaga reputasi dan aset perusahaan.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 8: Kerahasiaan (NDA) & Kekayaan Intelektual</div>
    <ol>
        <li>Dilarang membocorkan rahasia data tim, strategi, maupun source code sistem kepada pihak ketiga.</li>
        <li>Seluruh ciptaan dan hasil kerja menjadi milik eksklusif PIHAK PERTAMA.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 9: Tata Tertib & Sanksi SP</div>
    <ol>
        <li>Pelanggaran kerja dikenakan SP I, SP II, SP III masing-masing berlaku 6 bulan.</li>
        <li>Pelanggaran berat (kejahatan, suap, narkoba) mengakibatkan pemutusan hubungan kerja seketika.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 10: Pengunduran Diri (Resign) & Notice Period</div>
    <ol>
        <li>Pengunduran diri wajib memberitahukan tertulis sekurang-kurangnya <strong>{NOTICE_PERIOD} hari kalender</strong> sebelumnya.</li>
        <li>Wajib menyelesaikan serah terima tugas (handover) dan exit clearance perlengkapan kerja.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 11: Penyelesaian Perselisihan & Penutup</div>
    <ol>
        <li>Musyawarah bipartit 30 hari, berlanjut ke mediasi Disnaker dan Pengadilan Hubungan Industrial (PHI).</li>
        <li>Dibuat rangkap 2 (dua) asli bermaterai cukup dan memiliki kekuatan hukum yang sah.</li>
    </ol>
</div>
HTML;

        // 3. TEMPLATE PROFESSIONAL / OFISIAL
        $profContent = str_replace(
            'PKWT (Perjanjian Kerja Waktu Tertentu)',
            'Kontrak Profesional (Ofisial / Tenaga Ahli)',
            $pkwtContent
        );

        // 4. TEMPLATE FREELANCE / HARIAN LEPAS
        $freelanceContent = <<<'HTML'
<p>Pada hari ini, <strong>{TANGGAL_HARI_INI}</strong>, bertempat di kantor <strong>{COMPANY_NAME}</strong>, telah dibuat dan disepakati Perjanjian Kerja Lepas / Freelance antara pihak-pihak sebagai berikut:</p>

<table class="party-table">
    <tr>
        <td class="party-label">Nama Perusahaan</td>
        <td class="party-colon">:</td>
        <td class="party-val"><strong>{COMPANY_NAME}</strong></td>
    </tr>
    <tr>
        <td class="party-label">Alamat Kantor</td>
        <td class="party-colon">:</td>
        <td class="party-val">{COMPANY_ADDRESS}</td>
    </tr>
    <tr>
        <td class="party-label">Diwakili Oleh</td>
        <td class="party-colon">:</td>
        <td class="party-val">Direksi / Manajemen yang berwenang bertindak untuk dan atas nama Perseroan</td>
    </tr>
    <tr>
        <td class="party-label">Kedudukan</td>
        <td class="party-colon">:</td>
        <td class="party-val">Pemberi Kerja / Klien (disebut sebagai <strong>"PIHAK PERTAMA"</strong>)</td>
    </tr>
</table>

<p style="margin-top: 6px; margin-bottom: 2px;">Dan menerangkan identitas pekerja lepas:</p>

<table class="party-table">
    <tr>
        <td class="party-label">Nama Lengkap</td>
        <td class="party-colon">:</td>
        <td class="party-val"><strong>{NAMA_KARYAWAN}</strong></td>
    </tr>
    <tr>
        <td class="party-label">Nomor Induk Karyawan (NIK)</td>
        <td class="party-colon">:</td>
        <td class="party-val">#{NIK_KARYAWAN}</td>
    </tr>
    <tr>
        <td class="party-label">Tempat, Tanggal Lahir</td>
        <td class="party-colon">:</td>
        <td class="party-val">{TEMPAT_LAHIR}, {TANGGAL_LAHIR}</td>
    </tr>
    <tr>
        <td class="party-label">Jenis Kelamin</td>
        <td class="party-colon">:</td>
        <td class="party-val">{JENIS_KELAMIN}</td>
    </tr>
    <tr>
        <td class="party-label">Alamat Domisili</td>
        <td class="party-colon">:</td>
        <td class="party-val">{ALAMAT_KARYAWAN}</td>
    </tr>
    <tr>
        <td class="party-label">Kedudukan</td>
        <td class="party-colon">:</td>
        <td class="party-val">Pekerja Lepas / Freelancer (disebut sebagai <strong>"PIHAK KEDUA"</strong>)</td>
    </tr>
</table>

<p>PARA PIHAK sepakat untuk saling mengikatkan diri dalam Perjanjian Kerja Lepas dengan ketentuan pasal-pasal berikut:</p>

<div class="pasal-block">
    <div class="pasal-header">Pasal 1: Status Hubungan Kerja & Lingkup Penugasan</div>
    <ol>
        <li>Hubungan kerja antara PIHAK PERTAMA dan PIHAK KEDUA adalah hubungan kemitraan kerja lepas / freelance berbasis penugasan khusus, pertandingan, atau proyek berjalan.</li>
        <li>PIHAK KEDUA ditugaskan dengan peran/posisi sebagai <strong>{JABATAN}</strong> pada lingkup kegiatan Departemen <strong>{DEPARTEMEN}</strong>.</li>
        <li>Penugasan dapat dilaksanakan di lokasi utama <strong>{LOKASI_KANTOR}</strong>, venue pertandingan, maupun secara remote disesuaikan dengan kebutuhan kegiatan.</li>
        <li>Perjanjian ini tidak menimbulkan hubungan kerja tetap ataupun hubungan industrial jangka panjang.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 2: Jangka Waktu Perjanjian</div>
    <ol>
        <li>Perjanjian ini berlaku efektif terhitung sejak tanggal <strong>{TANGGAL_BERGABUNG}</strong> sampai dengan tanggal <strong>{TANGGAL_BERAKHIR}</strong>.</li>
        <li>Perpanjangan perjanjian dapat dilakukan berdasarkan kesepakatan tertulis kedua belah pihak dengan memperhatikan kebutuhan penugasan berikutnya.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 3: Ruang Lingkup Tugas & Hasil Kerja (Deliverables)</div>
    <p>PIHAK KEDUA berkewajiban menyelesaikan penugasan dan memberikan hasil kerja (deliverables) secara profesional yang mencakup:</p>
    {WORKING_SCOPE}
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 4: Waktu Kerja & Fleksibilitas</div>
    <ol>
        <li>Jadwal kerja PIHAK KEDUA bersifat fleksibel disesuaikan dengan jadwal penugasan proyek atau jadwal operasional pertandingan (kurang lebih <strong>{JAM_KERJA_MINGGUAN} jam per minggu</strong> atau berdasarkan kehadiran kegiatan).</li>
        <li>PIHAK KEDUA wajib mencatatkan kehadiran presensi mobile saat bertugas pada venue/kantor yang ditentukan.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 5: Honorarium, Imbalan Jasa & Pajak</div>
    <ol>
        <li>PIHAK PERTAMA setuju membayarkan imbalan jasa / honorarium kepada PIHAK KEDUA sebesar <strong>{GAJI_POKOK}</strong> per bulan atau sesuai termin kegiatan yang disepakati.</li>
        <li>Fasilitas / Tunjangan Tambahan (apabila ada): {TUNJANGAN_RUTIN}</li>
        <li>Pembayaran honorarium ditransfer ke rekening resmi PIHAK KEDUA: {REKENING_BANK}.</li>
        <li>Pemotongan Pajak Penghasilan (PPh 21 Bukan Pegawai / Tenaga Ahli Lepas) dilakukan sesuai ketentuan peraturan perpajakan yang berlaku ({STATUS_PTKP}).</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 6: Hak, Fasilitas & Batasan Manfaat</div>
    <ol>
        <li>PIHAK KEDUA memahami dan menyetujui bahwa status hubungan kerja lepas tidak memberikan hak atas jaminan purna kerja, Tunjangan Hari Raya (THR) karyawan tetap, maupun uang kompensasi PKWT Pasal 15 PP No. 35 Tahun 2021, kecuali diatur lain secara khusus.</li>
        <li>PIHAK PERTAMA menyediakan ID Pass kegiatan dan akomodasi penugasan matchday sesuai SOP operasional klub.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 7: Kerahasiaan (NDA) & Hak Cipta Hasil Kerja</div>
    <ol>
        <li>PIHAK KEDUA wajib menjaga kerahasiaan seluruh informasi internal, materi media sebelum rilis, data pemain, taktik, dan dokumen komersial milik PIHAK PERTAMA.</li>
        <li>Seluruh materi, hasil desain, rekaman foto/video, naskah, kode program, maupun karya yang dihasilkan selama penugasan menjadi Hak Kekayaan Intelektual (HAKI) eksklusif milik PIHAK PERTAMA.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 8: Pengakhiran Perjanjian</div>
    <ol>
        <li>Perjanjian berakhir otomatis pada saat berakhirnya masa penugasan sebagaimana tercantum dalam Pasal 2.</li>
        <li>Salah satu pihak dapat mengakhiri perjanjian sewaktu-waktu dengan pemberitahuan tertulis sekurang-kurangnya <strong>{NOTICE_PERIOD} hari kalender</strong> sebelumnya.</li>
        <li>PIHAK PERTAMA berhak memutus penugasan seketika apabila PIHAK KEDUA melakukan tindakan indisipliner berat atau mencemarkan nama baik klub.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 9: Penutup & Penyelesaian Perselisihan</div>
    <ol>
        <li>Segala perselisihan yang timbul akan diselesaikan secara musyawarah dan kekeluargaan.</li>
        <li>Perjanjian ini dibuat dalam rangkap 2 (dua) asli yang masing-masing mempunyai kekuatan hukum yang sama.</li>
    </ol>
</div>
HTML;

        // 5. TEMPLATE INTERNSHIP / MAGANG
        $internContent = <<<'HTML'
<p>Pada hari ini, <strong>{TANGGAL_HARI_INI}</strong>, bertempat di kantor <strong>{COMPANY_NAME}</strong>, telah dibuat dan disepakati Perjanjian Program Magang / Praktik Kerja (Internship Agreement) antara pihak-pihak sebagai berikut:</p>

<table class="party-table">
    <tr>
        <td class="party-label">Nama Perusahaan</td>
        <td class="party-colon">:</td>
        <td class="party-val"><strong>{COMPANY_NAME}</strong></td>
    </tr>
    <tr>
        <td class="party-label">Alamat Kantor</td>
        <td class="party-colon">:</td>
        <td class="party-val">{COMPANY_ADDRESS}</td>
    </tr>
    <tr>
        <td class="party-label">Diwakili Oleh</td>
        <td class="party-colon">:</td>
        <td class="party-val">Direksi / Manajemen HRD yang berwenang bertindak untuk dan atas nama Perseroan</td>
    </tr>
    <tr>
        <td class="party-label">Kedudukan</td>
        <td class="party-colon">:</td>
        <td class="party-val">Penyelenggara Magang (disebut sebagai <strong>"PIHAK PERTAMA"</strong>)</td>
    </tr>
</table>

<p style="margin-top: 6px; margin-bottom: 2px;">Dan menerangkan identitas peserta magang:</p>

<table class="party-table">
    <tr>
        <td class="party-label">Nama Lengkap</td>
        <td class="party-colon">:</td>
        <td class="party-val"><strong>{NAMA_KARYAWAN}</strong></td>
    </tr>
    <tr>
        <td class="party-label">Nomor Induk / NIK</td>
        <td class="party-colon">:</td>
        <td class="party-val">#{NIK_KARYAWAN}</td>
    </tr>
    <tr>
        <td class="party-label">Tempat, Tanggal Lahir</td>
        <td class="party-colon">:</td>
        <td class="party-val">{TEMPAT_LAHIR}, {TANGGAL_LAHIR}</td>
    </tr>
    <tr>
        <td class="party-label">Jenis Kelamin</td>
        <td class="party-colon">:</td>
        <td class="party-val">{JENIS_KELAMIN}</td>
    </tr>
    <tr>
        <td class="party-label">Alamat Domisili</td>
        <td class="party-colon">:</td>
        <td class="party-val">{ALAMAT_KARYAWAN}</td>
    </tr>
    <tr>
        <td class="party-label">Kedudukan</td>
        <td class="party-colon">:</td>
        <td class="party-val">Peserta Magang / Pemagang (disebut sebagai <strong>"PIHAK KEDUA"</strong>)</td>
    </tr>
</table>

<p>PARA PIHAK sepakat untuk mengikatkan diri dalam Program Pemagangan Kerja dengan ketentuan pasal-pasal berikut:</p>

<div class="pasal-block">
    <div class="pasal-header">Pasal 1: Maksud, Tujuan & Penempatan Magang</div>
    <ol>
        <li>Program Pemagangan diselenggarakan untuk memberikan wawasan, pelatihan keterampilan praktis, dan pengalaman kerja nyata dalam industri manajemen olahraga profesional.</li>
        <li>PIHAK KEDUA ditempatkan sebagai <strong>Intern - {JABATAN}</strong> pada Departemen <strong>{DEPARTEMEN}</strong> di bawah bimbingan langsung Mentor / Atasan Unit Kerja.</li>
        <li>Lokasi penempatan utama berada di <strong>{LOKASI_KANTOR}</strong>.</li>
        <li>Hubungan antara PARA PIHAK adalah murni hubungan pemagangan kerja dalam rangka pembelajaran, dan bukan merupakan hubungan kerja karyawan tetap.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 2: Jangka Waktu Program Magang</div>
    <ol>
        <li>Program Magang berlangsung selama masa kerja yang disepakati terhitung sejak <strong>{TANGGAL_BERGABUNG}</strong> sampai dengan <strong>{TANGGAL_BERAKHIR}</strong>.</li>
        <li>Evaluasi berkala terhadap proses pembelajaran dan performa peserta magang dilakukan setiap <strong>{DURASI_PROBATION} bulan</strong>.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 3: Ruang Lingkup Aktivitas Magang (Learning Scope)</div>
    <p>PIHAK KEDUA melaksanakan tugas-tugas pendukung kerja profesional dan program pembelajaran yang meliputi:</p>
    {WORKING_SCOPE}
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 4: Waktu Kehadiran & Tata Tertib Kerja</div>
    <ol>
        <li>Waktu pelaksanaan magang adalah <strong>{JAM_KERJA_MINGGUAN} jam per minggu</strong> mengikuti jam operasional kantor yang berlaku.</li>
        <li>PIHAK KEDUA wajib mencatatkan kehadiran presensi secara tertib pada aplikasi ERP.</li>
        <li>PIHAK KEDUA wajib mematuhi kode etik, tata krama, kerapian berpakaian, dan peraturan disiplin internal perusahaan.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 5: Uang Saku (Stipend) & Bantuan Operasional</div>
    <ol>
        <li>Sebagai bentuk dukungan operasional, PIHAK PERTAMA memberikan Uang Saku / Bantuan Transportasi sebesar <strong>{GAJI_POKOK}</strong> per bulan.</li>
        <li>Bantuan fasilitas tambahan (apabila ada): {TUNJANGAN_RUTIN}</li>
        <li>Uang saku ditransfer setiap akhir bulan ke rekening resmi PIHAK KEDUA: {REKENING_BANK}.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 6: Hak Peserta Magang & Sertifikat</div>
    <ol>
        <li>PIHAK KEDUA berhak mendapatkan bimbingan teknis dan arahan tugas dari mentor kerja.</li>
        <li>Setelah menyelesaikan program magang secara penuh dan memenuhi kriteria penilaian yang baik, PIHAK PERTAMA akan menerbitkan <strong>Surat Keterangan / Sertifikat Magang Resmi (Certificate of Internship)</strong>.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 7: Kerahasiaan Data (NDA) & Etika Profesi</div>
    <ol>
        <li>PIHAK KEDUA wajib menjaga kerahasiaan seluruh dokumen, data klub, strategi komunikasi, dan materi internal perusahaan.</li>
        <li>Dilarang mengunggah materi kerja yang belum dipublikasikan ke media sosial pribadi tanpa izin tertulis dari mentor/manajemen.</li>
    </ol>
</div>

<div class="pasal-block">
    <div class="pasal-header">Pasal 8: Pengakhiran Program Magang & Ketentuan Penutup</div>
    <ol>
        <li>Program magang berakhir sesuai jangka waktu yang telah disepakati pada Pasal 2.</li>
        <li>Apabila salah satu pihak berhalangan melanjutkan program, pemberitahuan tertulis disampaikan sekurang-kurangnya <strong>{NOTICE_PERIOD} hari kerja</strong> sebelumnya.</li>
        <li>Perjanjian ini dibuat dalam rangkap 2 (dua) yang masing-masing memiliki kekuatan hukum setara.</li>
    </ol>
</div>
HTML;

        ContractTemplate::updateOrCreate(
            ['contract_type' => 'PKWT'],
            [
                'nama_template' => 'Template Standar PKWT (Perjanjian Kerja Waktu Tertentu)',
                'judul_surat'   => 'SURAT PERJANJIAN KERJA WAKTU TERTENTU (PKWT)',
                'konten'        => $pkwtContent,
                'is_active'     => true,
            ]
        );

        ContractTemplate::updateOrCreate(
            ['contract_type' => 'PKWTT'],
            [
                'nama_template' => 'Template Standar PKWTT (Karyawan Tetap)',
                'judul_surat'   => 'SURAT PERJANJIAN KERJA WAKTU TIDAK TERTENTU (PKWTT)',
                'konten'        => $pkwttContent,
                'is_active'     => true,
            ]
        );

        ContractTemplate::updateOrCreate(
            ['contract_type' => 'Professional'],
            [
                'nama_template' => 'Template Kontrak Tenaga Ahli & Ofisial Profesional',
                'judul_surat'   => 'SURAT PERJANJIAN KERJA PROFESIONAL',
                'konten'        => $profContent,
                'is_active'     => true,
            ]
        );

        ContractTemplate::updateOrCreate(
            ['contract_type' => 'Freelance'],
            [
                'nama_template' => 'Template Perjanjian Kerja Lepas / Freelance',
                'judul_surat'   => 'SURAT PERJANJIAN KERJA HARIAN LEPAS / FREELANCE',
                'konten'        => $freelanceContent,
                'is_active'     => true,
            ]
        );

        ContractTemplate::updateOrCreate(
            ['contract_type' => 'Internship'],
            [
                'nama_template' => 'Template Perjanjian Program Magang / Internship',
                'judul_surat'   => 'SURAT PERJANJIAN PROGRAM MAGANG / INTERNSHIP',
                'konten'        => $internContent,
                'is_active'     => true,
            ]
        );
    }
}
