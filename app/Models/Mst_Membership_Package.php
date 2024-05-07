<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mst_Membership_Package extends Model
{
    use HasFactory;
    protected $table = 'mst__membership__packages';

    protected $fillable = [
        'package_title',
        'package_duration',
        'package_description',
        'package_price',
        'package_discount_price',
        'is_active',
        'deleted_by',
    ];
}
