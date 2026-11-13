<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Emagazine extends Model
{
    protected $table = 'digital_publications';

    protected $fillable = ['slug', 'title', 'description', 'issue_date', 'month', 'year', 'file_path', 'cover_image', 'is_available'];

    protected static string $publicationType = 'emagazine';

    protected static function booted(): void
    {
        static::addGlobalScope('publication_type', fn (Builder $query) => $query->where('type', static::$publicationType));
        static::creating(fn (Model $publication) => $publication->type = static::$publicationType);
    }

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'year' => 'integer',
            'is_available' => 'boolean',
        ];
    }
}
