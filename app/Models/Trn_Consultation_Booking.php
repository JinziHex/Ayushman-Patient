<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trn_Consultation_Booking extends Model
{
    use HasFactory;
    protected $table = 'trn_consultation_bookings';

    protected $fillable = [
        'booking_reference_number',
        'booking_type_id',
        'patient_id',
        'is_membership_available',
        'doctor_id',
        'branch_id',
        'booking_date',
        'time_slot_id',
        'booking_status_id',
        'availability_id',
        'therapy_id',
        'therapy_room_id',
        'wellness_id',
        'is_paid',
        'external_doctor_id',
        'booking_fee',
        'discount',
        'is_for_family_member',
        'family_member_id',
        'remaining_time',
        'booking_source',
        'preffered_slot',
        
    ];
}
