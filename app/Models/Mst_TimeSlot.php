<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mst_TimeSlot extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mst_timeslots';

    protected $primaryKey = 'id';
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'slot_name',
        'time_from',
        'time_to',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
