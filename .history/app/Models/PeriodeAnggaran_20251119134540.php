<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodeAnggaran extends Model
{
    use HasFactory;
    protected $table = 'tbl_periode_anggaran';
    protected $fillable = ['nama_periode', 'tanggal_mulai', 'tanggal_selesai', 'is_active'];
    
    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'is_active' => 'boolean',
    ];
}
