<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgriculturalJob extends Model
{
    protected $table = 'agricultural_jobs';

    protected $fillable = [
        'title',
        'description',
        'location',
        'salary',
        'contact_person',
        'contact_phone',
        'status',
    ];
}