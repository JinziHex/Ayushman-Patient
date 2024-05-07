<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mst_Branch extends Model
{
    use HasFactory;
    protected $table ='mst_branches';


    protected $fillable = [
        'branch_name',
        'is_active',
    ];

    protected $attributes = [
        'is_deleted' => false,
        'deleted_by' => false,
    ];
    
}
