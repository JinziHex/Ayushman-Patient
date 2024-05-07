<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
class Mst_Patient extends Authenticatable
{
    use HasFactory, HasApiTokens;
    protected $table = 'mst_patients';

    protected $fillable = [
        'patient_code',
        'patient_name',
        'patient_email',
        'patient_mobile',
        'patient_mobile_new',
        'patient_address',
        'patient_gender',
        'patient_dob',
         'patient_registration_type',
         'has_credit',
         'is_approved',
        'username',
        'password',
        'is_active',
        
    ];

    public function gender()
    {
      return $this->belongsTo(Sys_Gender::class,'patient_gender','id');
    }
}
