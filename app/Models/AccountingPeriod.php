<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingPeriod extends Model
{
    protected $table = 'tbl_accounting_periods';

    protected $fillable = [
        'nama_periode',
        'tanggal_mulai',
        'tanggal_selesai',
        'is_closed',
        'closed_at',
        'closed_by'
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'is_closed' => 'boolean',
        'closed_at' => 'datetime'
    ];

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
