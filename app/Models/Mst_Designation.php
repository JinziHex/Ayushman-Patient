<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mst_Designation extends Model
{
    use HasFactory;
    protected $table = 'mst__designations';

    protected $fillable = [
        'designation',
    ];
}
