<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pajak extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'tbl_pajak';

    /**
     * Relasi ke Akun GL (satu aturan Pajak terhubung ke satu Akun GL).
     */
    public function akunGl()
    {
        return $this->belongsTo(AkunGl::class, 'id_akun_pajak');
    }
}
