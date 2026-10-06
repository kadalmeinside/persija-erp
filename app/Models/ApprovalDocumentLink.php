<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
abstract class ApprovalDocumentLink extends Model
{
    public $timestamps = false;
    protected $guarded = [];
}
