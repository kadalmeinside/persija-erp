<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Task extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'tbl_tasks';

    protected $fillable = [
        'id_karyawan_creator',
        'id_karyawan_assignee',
        'id_departemen',
        'id_program_kerja',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'order_index',
    ];
    
    protected $casts = [
        'due_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'priority', 'due_date', 'title', 'id_karyawan_assignee'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Task '{$this->title}' {$eventName}");
    }

    public function creator()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan_creator');
    }

    public function assignee()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan_assignee');
    }

    public function departemen()
    {
        return $this->belongsTo(Departemen::class, 'id_departemen');
    }

    public function programKerja()
    {
        return $this->belongsTo(ProgramKerja::class, 'id_program_kerja');
    }
}
