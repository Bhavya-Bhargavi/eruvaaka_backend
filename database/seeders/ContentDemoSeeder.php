<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Epaper;
use App\Models\Emagazine;
use App\Models\ForumDiscussion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ContentDemoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['eruvaaka1.pdf', 'eruvaaka2.pdf'] as $fileName) {
            $sourcePath = app_path('images/'.$fileName);
            if (is_file($sourcePath)) {
                Storage::disk('local')->put('images/'.$fileName, file_get_contents($sourcePath));
            }
        }

        Book::updateOrCreate(['slug' => 'demo-farming-guide'], [
            'title' => 'Demo Farming Guide',
            'author' => 'Eruvaaka Demo Content',
            'description' => 'Temporary sample text; replace with client-provided book content.',
            'content' => "Demo book content\n\nThis temporary text confirms the read endpoint works. Replace it with approved book content before production.",
            'is_published' => true,
        ]);

        ForumDiscussion::updateOrCreate(['slug' => 'demo-community-discussion'], [
            'title' => 'Demo Community Discussion',
            'body' => 'Temporary discussion for testing comments. Replace with client-approved forum content.',
            'is_published' => true,
        ]);

        Epaper::updateOrCreate(['slug' => 'demo-epaper'], [
            'title' => 'Eruvaaka E-Paper',
            'issue_date' => now()->toDateString(),
            'file_path' => 'images/eruvaaka1.pdf',
            'is_available' => true,
        ]);

        Emagazine::updateOrCreate(['slug' => 'demo-emagazine'], [
            'title' => 'Eruvaaka E-Magazine',
            'issue_date' => now()->toDateString(),
            'file_path' => 'images/eruvaaka2.pdf',
            'is_available' => true,
        ]);
    }
}