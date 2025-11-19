<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AkunGl extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'tbl_akun_gl';

    /**
     * Relasi ke Pajak (satu Akun GL bisa digunakan oleh banyak aturan Pajak).
     */
    public function pajak()
    {
        return $this->hasMany(Pajak::class, 'id_akun_pajak');
    }
}
