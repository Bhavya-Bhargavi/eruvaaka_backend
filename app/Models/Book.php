<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = ['slug', 'title', 'author', 'description', 'content', 'cover_image', 'is_published'];
}