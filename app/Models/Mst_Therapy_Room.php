<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mst_Therapy_Room extends Model
{
    use HasFactory;
    protected $table = 'mst_therapy_rooms';


    protected $fillable = [
        'branch_id',
        'room_name',
        'is_active',
    ];


    public function branch()
    {
        return $this->belongsTo(Mst_Branch::class,'branch_id','id');
    }
}
