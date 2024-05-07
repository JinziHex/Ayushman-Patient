<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trn_Patient_Otp extends Model
{
    use HasFactory;
    protected $table = 'trn_patient_otp';

    protected $fillable = [
        'patient_id',
        'otp',
        'otp_type',
        'verified',
        'otp_expire_at',
    ];
}
