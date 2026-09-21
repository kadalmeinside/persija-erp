<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisCuti extends Model
{
    use HasFactory;

    protected $table = 'tbl_jenis_cuti';

    protected $guarded = ['id'];

    protected $casts = [
        'kuota_default' => 'integer',
        'bisa_mundur' => 'boolean',
        'khusus_perempuan' => 'boolean',
        'is_unlimited' => 'boolean',
        'wajib_lampiran' => 'boolean'
    ];
}
