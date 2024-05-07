<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trn_Notification extends Model
{
    use HasFactory;

    protected $table = 'trn__notifications';

    protected $primaryKey = 'id';
    
    protected $fillable = [
        'patient_id',
        'title',
        'content',
        'read_status',
    ];
}
