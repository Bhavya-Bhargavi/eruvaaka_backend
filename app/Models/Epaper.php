<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Epaper extends Model
{
    protected $table = 'digital_publications';
    protected $fillable = ['slug', 'title', 'issue_date', 'file_path', 'is_available'];

    protected static string $publicationType = 'epaper';

    protected static function booted(): void
    {
        static::addGlobalScope('publication_type', fn (Builder $query) => $query->where('type', static::$publicationType));
        static::creating(fn (Model $publication) => $publication->type = static::$publicationType);
    }
}