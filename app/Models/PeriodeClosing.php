<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Carbon\Carbon;

class PeriodeClosing extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'tbl_periode_closing';

    protected $fillable = [
        'bulan',
        'tahun',
        'status',
        'closed_by',
        'reopened_by',
        'catatan',
        'closed_at',
        'reopened_at'
    ];

    protected $casts = [
        'closed_at' => 'datetime',
        'reopened_at' => 'datetime',
        'bulan' => 'integer',
        'tahun' => 'integer'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('finance');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function reopenedBy()
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    /**
     * Helper check if a date is within a closed period
     */
    public static function isDateClosed($dateString)
    {
        try {
            $date = Carbon::parse($dateString);
            $bulan = $date->month;
            $tahun = $date->year;

            $periode = self::where('bulan', $bulan)
                           ->where('tahun', $tahun)
                           ->first();

            return $periode && $periode->status === 'Closed';
        } catch (\Exception $e) {
            return false;
        }
    }
}
