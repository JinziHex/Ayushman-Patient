<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mst_Doctor extends Model
{
    use HasFactory;
    protected $table = 'mst__doctors';

    protected $fillable = [
        'user_id',
        'branch_id',
        'designation_id',
        'qualification',
        'available_timeslots',
    ];
}
