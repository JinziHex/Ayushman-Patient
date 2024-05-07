<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mst_Tax extends Model
{
    use HasFactory;
    protected $table = 'mst_taxes';

    protected $primaryKey = 'tax_id';
}
