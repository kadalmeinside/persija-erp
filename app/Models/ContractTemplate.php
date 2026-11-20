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
                $nama = e($komponen->komponenGaji->nama_komponen ?? 'Tunjangan Rutin');
                $nom = number_format($komponen->nominal, 0, ',', '.');
                $ket = $komponen->keterangan ? ' (' . e($komponen->keterangan) . ')' : '';
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
            '{COMPANY_NAME}'             => e($company['name'] ?? 'PT PERSIJA JAYA JAKARTA'),
            '{COMPANY_ADDRESS}'          => e($company['address'] ?? '-'),
            '{NOMOR_KONTRAK}'            => e($nomorKontrak),
            '{NAMA_KARYAWAN}'            => e($karyawan->nama_lengkap ?? '-'),
            '{NIK_KARYAWAN}'             => e($karyawan->nomor_induk_karyawan ?? '-'),
            '{TEMPAT_LAHIR}'             => e($karyawan->tempat_lahir ?? '-'),
            '{TANGGAL_LAHIR}'            => e($tglLahir),
            '{JENIS_KELAMIN}'            => e($gender),
            '{ALAMAT_KARYAWAN}'          => e($karyawan->alamat ?? 'Sesuai data terdaftar'),
            '{JABATAN}'                  => e($karyawan->jabatan ?? '-'),
            '{DEPARTEMEN}'               => e($karyawan->departemen->nama_departemen ?? 'Operasional Klub'),
            '{LOKASI_KANTOR}'            => e($karyawan->lokasiKantor->nama_kantor ?? 'Kantor Pusat / Lapangan Resmi'),
            '{TANGGAL_BERGABUNG}'        => e($tglBergabung),
            '{TANGGAL_BERAKHIR}'         => e($tglBerakhir),
            '{DURASI_PROBATION}'         => e((string) ($karyawan->durasi_probation_bulan ?? 3)),
            '{TANGGAL_SELESAI_PROBATION}'=> e($tglSelesaiProbation),
            '{JAM_KERJA_MINGGUAN}'       => e((string) ($karyawan->jam_kerja_mingguan ?? 40)),
            '{NOTICE_PERIOD}'            => e((string) ($karyawan->notice_period_hari ?? 30)),
            '{GAJI_POKOK}'               => e('Rp ' . number_format($karyawan->gaji_pokok ?? 0, 0, ',', '.')),
            '{TUNJANGAN_RUTIN}'          => $tunjanganHtml,
            '{REKENING_BANK}'            => e($bankStr),
            '{STATUS_PTKP}'              => e($karyawan->status_ptkp ?? 'TK/0'),
            '{NO_BPJS_KETENAGAKERJAAN}'  => e($karyawan->no_bpjs_ketenagakerjaan ?? 'Dalam proses pendaftaran'),
            '{NO_BPJS_KESEHATAN}'        => e($karyawan->no_bpjs_kesehatan ?? 'Dalam proses pendaftaran'),
            '{WORKING_SCOPE}'            => $scopeHtml,
            '{TANGGAL_HARI_INI}'         => e(Carbon::now()->translatedFormat('d F Y')),
        ];

        $rendered = str_replace(array_keys($replacements), array_values($replacements), $rawContent);
        $rendered = preg_replace('/<(script|iframe|object|embed)\b[^>]*>.*?<\/\1>/is', '', $rendered) ?? $rendered;
        $rendered = preg_replace('/\son\w+\s*=\s*(["\']).*?\1/is', '', $rendered) ?? $rendered;
        return preg_replace('/javascript\s*:/i', '', $rendered) ?? $rendered;
    }
}
