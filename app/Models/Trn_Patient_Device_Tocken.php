<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trn_Patient_Device_Tocken extends Model
{
    use HasFactory;
    protected $table = 'trn__patient__device__tockens';

    protected $fillable = [
        'patient_device_token_id',
        'patient_id',
        'patient_device_token',
    ];
}
