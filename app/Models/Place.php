<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    use HasFactory;

    protected $fillable = ['location_name'];

    // لا يوجد timestamps في الجدول (اختياري حسب جدولك)
    public $timestamps = false;
}
