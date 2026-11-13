<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = ['slug', 'title', 'category', 'description', 'content', 'cover_image', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }
}
