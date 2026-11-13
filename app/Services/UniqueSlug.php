<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UniqueSlug
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function make(string $modelClass, string $value): string
    {
        $base = substr(Str::slug($value) ?: 'content', 0, 220);
        $slug = $base;
        $suffix = 1;

        while ($modelClass::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
