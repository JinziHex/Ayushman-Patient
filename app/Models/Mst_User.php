<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;


class Mst_User extends Authenticatable
{
    use HasFactory,Notifiable;
    protected $table = 'mst_users';

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'username',
        'password',
        'staff_id',
        'user_type_id',
        'email',
        'remember_token',
        'is_active',
        'last_login_time',
        'created_by',
        'last_updated_by',
        'deleted_by',
        'is_deleted',
    ];

    public function userType()
    {
        return $this->belongsTo(Mst_Master_Value::class, 'user_type_id', 'id');
    }

    public function staff()
    {
        return $this->belongsTo(Mst_Staff::class, 'staff_id', 'staff_id');
    }   
}