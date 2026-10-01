<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumDiscussion extends Model
{
    protected $fillable = ['slug', 'title', 'body', 'is_published'];

    public function comments(): HasMany
    {
        return $this->hasMany(ForumComment::class);
    }
}