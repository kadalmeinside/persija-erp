<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetDetail extends Model
{
    use HasFactory;
    protected $table = 'tbl_budget_detail';
    protected $fillable = ['id_budget_master', 'bulan', 'tahun', 'nominal_pacing'];

    public function header()
    {
        return $this->belongsTo(BudgetMaster::class, 'id_budget_master');
    }
}
