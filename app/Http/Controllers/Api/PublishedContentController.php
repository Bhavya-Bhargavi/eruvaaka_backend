<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Book;
use App\Models\BookPurchase;
use App\Models\Emagazine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PublishedContentController extends Controller
{
    public function books(Request $request): JsonResponse
    {
        $perPage = $this->perPage($request);
        $books = Book::select(['id', 'slug', 'title', 'author', 'category', 'description', 'cover_image', 'pdf_path', 'price_minor'])
            ->where('is_published', true)
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'data' => $books->getCollection()->map(fn (Book $book): array => [
                'id' => $book->id,
                'slug' => $book->slug,
                'title' => $book->title,
                'author' => $book->author,
                'category' => $book->category,
                'description' => $book->description,
                'cover_url' => $this->coverUrl($book->cover_image),
                'pdf_download_url' => $book->pdf_path ? route('api.v2.books.download', $book) : null,
                'price_minor' => $book->price_minor,
                'currency' => 'INR',
            ]),
            'pagination' => $this->pagination($books),
        ]);
    }

    public function book(Book $book, Request $request): JsonResponse
    {
        abort_unless($book->is_published, 404);
        if ($failure = $this->paidBookAccessFailure($book, $request)) {
            return $failure;
        }

        return response()->json(['data' => [
            'id' => $book->id,
            'slug' => $book->slug,
            'title' => $book->title,
            'author' => $book->author,
            'category' => $book->category,
            'description' => $book->description,
            'content' => $book->content,
            'cover_url' => $this->coverUrl($book->cover_image),
            'pdf_download_url' => $book->pdf_path ? route('api.v2.books.download', $book) : null,
            'price_minor' => $book->price_minor,
            'currency' => 'INR',
        ]]);
    }

    public function downloadBook(Book $book, Request $request): Response
    {
        abort_unless($book->is_published && $book->pdf_path, 404);
        if ($failure = $this->paidBookAccessFailure($book, $request)) {
            return $failure;
        }
        abort_if(str_contains($book->pdf_path, '..') || str_starts_with($book->pdf_path, '/'), 404);
        abort_unless(Storage::disk('local')->exists($book->pdf_path), 404);

        return Storage::disk('local')->download($book->pdf_path);
    }

    public function magazines(Request $request): JsonResponse
    {
        $perPage = $this->perPage($request);
        $magazines = Emagazine::select(['id', 'slug', 'title', 'description', 'month', 'year', 'issue_date', 'cover_image'])
            ->where('is_available', true)
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'data' => $magazines->getCollection()->map(fn (Emagazine $magazine): array => [
                'id' => $magazine->id,
                'slug' => $magazine->slug,
                'title' => $magazine->title,
                'description' => $magazine->description,
                'month' => $magazine->month,
                'year' => $magazine->year,
                'issue_date' => $magazine->issue_date,
                'cover_url' => $this->coverUrl($magazine->cover_image),
                'pdf_download_url' => route('api.v2.magazines.download', $magazine),
            ]),
            'pagination' => $this->pagination($magazines),
        ]);
    }

    public function magazine(Emagazine $magazine): JsonResponse
    {
        abort_unless($magazine->is_available, 404);

        return response()->json(['data' => [
            'id' => $magazine->id,
            'slug' => $magazine->slug,
            'title' => $magazine->title,
            'description' => $magazine->description,
            'month' => $magazine->month,
            'year' => $magazine->year,
            'issue_date' => $magazine->issue_date,
            'cover_url' => $this->coverUrl($magazine->cover_image),
            'pdf_download_url' => route('api.v2.magazines.download', $magazine),
        ]]);
    }

    public function downloadMagazine(Emagazine $magazine): Response
    {
        abort_unless($magazine->is_available, 404);
        abort_if(str_contains($magazine->file_path, '..') || str_starts_with($magazine->file_path, '/'), 404);
        abort_unless(Storage::disk('local')->exists($magazine->file_path), 404);

        return Storage::disk('local')->download($magazine->file_path);
    }

    public function articles(Request $request): JsonResponse
    {
        $perPage = $this->perPage($request);
        $articles = Article::select(['id', 'slug', 'title', 'category', 'description', 'cover_image', 'created_at'])
            ->where('is_published', true)
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'data' => $articles->getCollection()->map(fn (Article $article): array => [
                'id' => $article->id,
                'slug' => $article->slug,
                'title' => $article->title,
                'category' => $article->category,
                'description' => $article->description,
                'cover_url' => $this->coverUrl($article->cover_image),
                'created_at' => $article->created_at,
            ]),
            'pagination' => $this->pagination($articles),
        ]);
    }

    public function article(Article $article): JsonResponse
    {
        abort_unless($article->is_published, 404);

        return response()->json(['data' => [
            'id' => $article->id,
            'slug' => $article->slug,
            'title' => $article->title,
            'category' => $article->category,
            'description' => $article->description,
            'content' => $article->content,
            'cover_url' => $this->coverUrl($article->cover_image),
            'created_at' => $article->created_at,
        ]]);
    }

    private function perPage(Request $request): int
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        return (int) ($validated['per_page'] ?? 15);
    }

    private function pagination(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ];
    }

    private function paidBookAccessFailure(Book $book, Request $request): ?JsonResponse
    {
        if ((int) $book->price_minor <= 0) {
            return null;
        }

        $claims = $request->attributes->get('jwt_claims');
        if (! is_array($claims) || ! isset($claims['id'])) {
            return response()->json(['message' => 'Authentication required to read this book'], 401);
        }

        if (! BookPurchase::where('user_id', (int) $claims['id'])->where('book_id', $book->id)->exists()) {
            return response()->json(['message' => 'Purchase required to read this book'], 403);
        }

        return null;
    }

    private function coverUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://'])
            ? $path
            : Storage::disk('public')->url($path);
    }
}
