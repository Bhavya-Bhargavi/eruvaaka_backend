<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = ['slug', 'title', 'author', 'category', 'description', 'content', 'cover_image', 'pdf_path', 'price_minor', 'is_published'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'price_minor' => 'integer',
        ];
    }
}
