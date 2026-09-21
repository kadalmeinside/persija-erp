<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Departemen extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'tbl_departemen';

    protected $fillable = ['nama_departemen', 'id_akun_beban_gaji', 'id_karyawan_kepala'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama_departemen', 'id_karyawan_kepala'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Departemen {$this->nama_departemen} {$eventName}");
    }

    public function akunBebanGaji()
    {
        return $this->belongsTo(AkunGl::class, 'id_akun_beban_gaji');
    }

    /**
     * Relasi ke Karyawan sebagai Kepala Departemen.
     */
    public function kepala()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan_kepala');
    }

    /**
     * Relasi ke Karyawan (satu Dept memiliki banyak Karyawan).
     */
    public function karyawan()
    {
        return $this->hasMany(Karyawan::class, 'id_departemen');
    }

    /**
     * Relasi ke Budget (satu Dept memiliki banyak baris Budget).
     */
    public function budgetMaster()
    {
        return $this->hasMany(BudgetMaster::class, 'id_departemen');
    }
}
