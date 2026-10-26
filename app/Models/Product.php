<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'source_id',
        'name',
        'slug',
        'description',
        'permalink',
        'price_minor',
        'currency_code',
        'currency_minor_unit',
        'image_url',
        'in_stock',
    ];

    protected function casts(): array
    {
        return [
            'price_minor' => 'integer',
            'currency_minor_unit' => 'integer',
            'in_stock' => 'boolean',
        ];
    }
}