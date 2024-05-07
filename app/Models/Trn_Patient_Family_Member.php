<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trn_Patient_Family_Member extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'trn_patient_family_member';

    protected $primaryKey = 'id';
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'patient_id',
        'family_member_name',
        'gender_id',
        'blood_group_id',
        'date_of_birth',
        'created_by',
        'is_active',
        'verified',
        'mobile_number_new',
        'address',
        'mobile_number',
        'email_address',
        'relationship_id',
        'deleted_at',
    ];
}
