<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Book;
use App\Models\Emagazine;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $books = Book::query();
        $magazines = Emagazine::query();
        $articles = Article::query();

        $recentUploads = collect()
            ->concat($books->latest()->limit(5)->get()->map(fn (Book $book): array => [
                'title' => $book->title,
                'type' => 'Book',
                'created_at' => $book->created_at,
                'url' => route('admin.books.edit', $book),
            ]))
            ->concat($magazines->latest()->limit(5)->get()->map(fn (Emagazine $magazine): array => [
                'title' => $magazine->title,
                'type' => 'Magazine',
                'created_at' => $magazine->created_at,
                'url' => route('admin.magazines.edit', $magazine),
            ]))
            ->concat($articles->latest()->limit(5)->get()->map(fn (Article $article): array => [
                'title' => $article->title,
                'type' => 'Article',
                'created_at' => $article->created_at,
                'url' => route('admin.articles.edit', $article),
            ]))
            ->sortByDesc('created_at')
            ->take(8)
            ->values();

        return view('admin.dashboard', [
            'bookCount' => Book::count(),
            'magazineCount' => Emagazine::count(),
            'articleCount' => Article::count(),
            'publishedCount' => Book::where('is_published', true)->count()
                + Emagazine::where('is_available', true)->count()
                + Article::where('is_published', true)->count(),
            'recentUploads' => $recentUploads,
        ]);
    }
}
