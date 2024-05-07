<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mst_Wellness extends Model
{
    use HasFactory;
    protected $primaryKey = 'wellness_id';

    protected $table = 'mst_wellness';

    protected $fillable = [
        'wellness_name',
        'wellness_cost',
        'remarks',
        'is_active',
        'is_deleted',
        'deleted_by',
    ];
}
