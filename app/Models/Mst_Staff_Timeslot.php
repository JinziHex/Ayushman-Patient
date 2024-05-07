<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mst_Staff_Timeslot extends Model
{
    use HasFactory;
    protected $table = 'mst__staff__timeslots';


    public function Doctor()
    {
        return $this->belongsTo(Mst_Staff::class, 'staff_id', 'staff_id');
    }

    public function weekDay()
    {
        return $this->belongsTo(Mst_Master_Value::class, 'week_day', 'id');
    }

     public function timeSlot()
    {
        return $this->belongsTo(Mst_TimeSlot::class,'timeslot','id');
    }
     public function bookings()
    {
        return $this->hasMany(Trn_Consultation_Booking::class, 'staff_timeslot_id', 'id');
    }
}
