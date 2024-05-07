<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mst_Therapy extends Model
{
    use HasFactory;
    protected $table = 'mst_therapies';
    protected $fillable = [
        'therapy_name',
        'therapy_cost',
        'remarks',
        'is_active',
    ];
}