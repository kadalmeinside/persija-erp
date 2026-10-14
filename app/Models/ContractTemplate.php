<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractTemplate extends Model
{
    use HasFactory;

    protected $table = 'tbl_contract_templates';

    protected $fillable = [
        'nama_template',
        'contract_type',
        'judul_surat',
        'konten',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Parse and render all dynamic placeholders against Employee & Company data.
     */
    public static function parseContent(string $rawContent, Karyawan $karyawan, array $company): string
    {
        $primaryBank = $karyawan->primary_bank;
        $bankStr = $primaryBank 
            ? "Bank: {$primaryBank->nama_bank} | No. Rekening: {$primaryBank->nomor_rekening} | a.n: {$primaryBank->atas_nama_rekening}"
            : "Rekening Bank Karyawan Terdaftar";

        // Format Working Scope as ordered/unordered list HTML
        $scopeHtml = '';
        if (!empty($karyawan->working_scope) && is_array($karyawan->working_scope)) {
            $scopeHtml .= '<ul>';
            foreach ($karyawan->working_scope as $scopeItem) {
                $scopeHtml .= '<li>' . htmlspecialchars($scopeItem, ENT_QUOTES, 'UTF-8') . '</li>';
            }
            $scopeHtml .= '</ul>';
        } else {
            $scopeHtml = '<ul><li>Melaksanakan seluruh tanggung jawab dan kewajiban profesional sesuai jabatan ' . htmlspecialchars($karyawan->jabatan, ENT_QUOTES, 'UTF-8') . '.</li></ul>';
        }

        // Format Komponen Tunjangan Rutin
        $tunjanganHtml = '';
        if ($karyawan->komponenGaji && count($karyawan->komponenGaji) > 0) {
            $tunjanganHtml .= '<ul>';
            foreach ($karyawan->komponenGaji as $komponen) {
                $nama = $komponen->komponenGaji->nama_komponen ?? 'Tunjangan Rutin';
                $nom = number_format($komponen->nominal, 0, ',', '.');
                $ket = $komponen->keterangan ? " ({$komponen->keterangan})" : '';
                $tunjanganHtml .= "<li>{$nama}: Rp {$nom} per bulan{$ket}</li>";
            }
            $tunjanganHtml .= '</ul>';
        } else {
            $tunjanganHtml = 'Tidak ada tunjangan rutin tambahan di luar gaji pokok.';
        }

        $gender = ($karyawan->jenis_kelamin == 'L') ? 'Laki-laki' : (($karyawan->jenis_kelamin == 'P') ? 'Perempuan' : '-');
        $tglLahir = $karyawan->tgl_lahir ? Carbon::parse($karyawan->tgl_lahir)->translatedFormat('d F Y') : '-';
        $tglBergabung = $karyawan->tgl_bergabung ? Carbon::parse($karyawan->tgl_bergabung)->translatedFormat('d F Y') : '-';
        $tglBerakhir = $karyawan->tanggal_berakhir_kontrak ? Carbon::parse($karyawan->tanggal_berakhir_kontrak)->translatedFormat('d F Y') : 'Selesai / Masa Kerja Definitif';
        $tglSelesaiProbation = $karyawan->tanggal_selesai_probation ? Carbon::parse($karyawan->tanggal_selesai_probation)->translatedFormat('d F Y') : '-';
        $nomorKontrak = $karyawan->nomor_kontrak ?? ('PKWT/HRD/' . date('Y') . '/' . ($karyawan->nomor_induk_karyawan ?? 'KARYAWAN'));

        $replacements = [
            '{COMPANY_NAME}'             => $company['name'] ?? 'PT PERSIJA JAYA JAKARTA',
            '{COMPANY_ADDRESS}'          => $company['address'] ?? '-',
            '{NOMOR_KONTRAK}'            => $nomorKontrak,
            '{NAMA_KARYAWAN}'            => $karyawan->nama_lengkap ?? '-',
            '{NIK_KARYAWAN}'             => $karyawan->nomor_induk_karyawan ?? '-',
            '{TEMPAT_LAHIR}'             => $karyawan->tempat_lahir ?? '-',
            '{TANGGAL_LAHIR}'            => $tglLahir,
            '{JENIS_KELAMIN}'            => $gender,
            '{ALAMAT_KARYAWAN}'          => $karyawan->alamat ?? 'Sesuai data terdaftar',
            '{JABATAN}'                  => $karyawan->jabatan ?? '-',
            '{DEPARTEMEN}'               => $karyawan->departemen->nama_departemen ?? 'Operasional Klub',
            '{LOKASI_KANTOR}'            => $karyawan->lokasiKantor->nama_kantor ?? 'Kantor Pusat / Lapangan Resmi',
            '{TANGGAL_BERGABUNG}'        => $tglBergabung,
            '{TANGGAL_BERAKHIR}'         => $tglBerakhir,
            '{DURASI_PROBATION}'         => (string) ($karyawan->durasi_probation_bulan ?? 3),
            '{TANGGAL_SELESAI_PROBATION}'=> $tglSelesaiProbation,
            '{JAM_KERJA_MINGGUAN}'       => (string) ($karyawan->jam_kerja_mingguan ?? 40),
            '{NOTICE_PERIOD}'            => (string) ($karyawan->notice_period_hari ?? 30),
            '{GAJI_POKOK}'               => 'Rp ' . number_format($karyawan->gaji_pokok ?? 0, 0, ',', '.'),
            '{TUNJANGAN_RUTIN}'          => $tunjanganHtml,
            '{REKENING_BANK}'            => $bankStr,
            '{STATUS_PTKP}'              => $karyawan->status_ptkp ?? 'TK/0',
            '{NO_BPJS_KETENAGAKERJAAN}'  => $karyawan->no_bpjs_ketenagakerjaan ?? 'Dalam proses pendaftaran',
            '{NO_BPJS_KESEHATAN}'        => $karyawan->no_bpjs_kesehatan ?? 'Dalam proses pendaftaran',
            '{WORKING_SCOPE}'            => $scopeHtml,
            '{TANGGAL_HARI_INI}'         => Carbon::now()->translatedFormat('d F Y'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $rawContent);
    }
}
