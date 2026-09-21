<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanCuti extends Model
{
    use HasFactory;

    protected $table = 'tbl_pengajuan_cuti';

    protected $guarded = ['id'];

    protected $casts = [
        'tgl_mulai' => 'date',
        'tgl_selesai' => 'date',
        'jumlah_hari' => 'integer'
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function jenisCuti()
    {
        return $this->belongsTo(JenisCuti::class, 'id_jenis_cuti');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'id_approver');
    }

    public function approvalProcess()
    {
        return $this->hasMany(ApprovalProcess::class, 'id_cuti')->orderBy('level_order', 'asc');
    }
}
