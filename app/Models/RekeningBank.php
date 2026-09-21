<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RekeningBank extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_rekening_bank';

    protected $fillable = [
        'nama_bank',
        'nomor_rekening',
        'atas_nama_rekening',
        'cabang',
        'owner_id',
        'owner_type',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    /**
     * Get the parent owner model (Vendor or Karyawan).
     */
    public function owner()
    {
        return $this->morphTo();
    }
}