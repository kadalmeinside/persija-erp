<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalRule extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database.
     */
    protected $table = 'tbl_approval_rules';

    protected $guarded = ['id'];

    protected $casts = [
        'min_amount'  => 'decimal:2',
        'level_order' => 'integer',
    ];

    /**
     * Relasi ke Departemen (Aturan ini milik departemen mana?)
     */
    public function departemen()
    {
        return $this->belongsTo(Departemen::class, 'id_departemen');
    }

    /**
     * Relasi ke Karyawan yang ditunjuk sebagai Approver.
     */
    public function karyawanApprover()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan_approver');
    }
}