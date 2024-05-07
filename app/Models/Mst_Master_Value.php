<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mst_Master_Value extends Model
{
    use HasFactory;

    protected $table = 'mst_master_values';

    protected $fillable = [
        'master_id ',
        'group_id ',
        'master_value',
        'is_active',
    ];

}
