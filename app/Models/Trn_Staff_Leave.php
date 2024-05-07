<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Trn_Staff_Leave extends Model
{
    use HasFactory,SoftDeletes;
    public $timestamps = true;
    protected $table = 'staff_leave';

    protected $fillable = [
        'branch_id',
        'staff_id',
        'leave_type',
        'days',
        'from_date',
        'to_date',
        'reason',
        'start_day',
        'end_day',
        'created_at',
        'updated_at'
    ];
    // public function staff()
    // {
    //     return $this->belongsTo(Mst_Staff::class, 'staff_id');
    // }
}
