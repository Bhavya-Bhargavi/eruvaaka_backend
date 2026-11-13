<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AdminFileStorage
{
    public function store(UploadedFile $file, string $directory, string $disk): string
    {
        $path = $file->store($directory, $disk);

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('The uploaded file could not be stored.');
        }

        return $path;
    }

    public function delete(?string $path, string $disk): void
    {
        if ($path === null || $path === '') {
            return;
        }

        if (str_contains($path, '..') || str_starts_with($path, '/')) {
            throw new RuntimeException('Refusing to delete an invalid stored-file path.');
        }

        if (Storage::disk($disk)->exists($path) && ! Storage::disk($disk)->delete($path)) {
            throw new RuntimeException('A replaced or deleted file could not be removed from storage.');
        }
    }
}
