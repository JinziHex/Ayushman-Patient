<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mst_Staff extends Model
{
    use HasFactory;
    protected $table = 'mst_staffs';

    protected $fillable = [
        'staff_type',
        'employement_type',
        'staff_code',
        'staff_name',
        'password',
        'staff_name',
        'gender',
        'is_active',
        'branch_id',
        'date_of_birth',
        'staff_email',
        'staff_contact_number',
        'staff_address',
        'staff_qualification ',
        'staff_work_experience',
        'staff_logon_type',
        'staff_commission_type',
        'staff_commission',
        'staff_booking_fee',
        'last_login_time',
    ];
}
