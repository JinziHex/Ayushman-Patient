<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mst_Therapy_Room_Slot extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'mst__therapy__room__slots';
    protected $primaryKey = 'id';
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'therapy_room_id',
        'week_day',
        'timeslot',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at',
    ];

    public function therapyRoom()
    {
        return $this->belongsTo(Mst_Therapy_Room::class, 'therapy_room_id', 'id');
    }

    public function weekDay()
    {
        return $this->belongsTo(Mst_Master_Value::class, 'week_day', 'id');
    }

    public function slot()
    {
        return $this->belongsTo(Mst_TimeSlot::class, 'timeslot', 'id');
    }
}
