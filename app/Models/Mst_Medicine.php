<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mst_Medicine extends Model
{
    use HasFactory;
    protected $table = 'mst_medicines';

    public function medicinecategory()
    {
        return $this->belongsTo(Mst_Medicine_Category::class, 'medicine_category_id', 'id');
    }

    public function unit()
    {
        return $this->belongsTo(Mst_Unit::class, 'unit_id', 'id');
    }
    
    public function tax()
    {
        return $this->belongsTo(Mst_Tax::class, 'tax_id', 'id');
    }
    
}
