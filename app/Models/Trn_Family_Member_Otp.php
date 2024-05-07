<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trn_Family_Member_Otp extends Model
{
    use HasFactory;
    protected $table = 'trn__family__member__otps';

    protected $fillable = [
        'patient_id',
        'family_member_id',
        'otp',
        'verified',
        'otp_expire_at',
    ];
}
