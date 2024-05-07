<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mst_Wellness_Therapyrooms extends Model
{
    use HasFactory;
    protected $table = 'mst__wellness__therapyrooms';

    protected $fillable = [
        'wellness_id',
        'branch_id',
        'therapy_room_id',
    ];

}
