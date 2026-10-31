<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = ['slug', 'title', 'author', 'description', 'content', 'cover_image', 'price_minor', 'is_published'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'price_minor' => 'integer',
        ];
    }
}