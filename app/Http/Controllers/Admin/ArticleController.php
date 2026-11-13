<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\AdminFileStorage;
use App\Services\AdminPanelNotifier;
use App\Services\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ArticleController extends Controller
{
    public function index(): View
    {
        return view('admin.articles.index', ['articles' => Article::latest()->paginate(15)]);
    }

    public function create(): View
    {
        return view('admin.articles.form', ['article' => new Article]);
    }

    public function store(Request $request, AdminFileStorage $files, UniqueSlug $slugs, AdminPanelNotifier $notifications): RedirectResponse
    {
        $data = $this->validated($request);
        $storedCover = null;

        try {
            if ($request->hasFile('cover')) {
                $storedCover = $files->store($request->file('cover'), 'covers/articles', 'public');
                $data['cover_image'] = $storedCover;
            }
            $data['slug'] = $slugs->make(Article::class, $data['title']);
            $data['is_published'] = false;
            unset($data['cover']);
            $article = Article::create($data);
        } catch (Throwable $exception) {
            if ($storedCover !== null) {
                try {
                    $files->delete($storedCover, 'public');
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }

            throw $exception;
        }

        $notifications->notifyChange($request, 'created as draft', 'Article', $article->title);

        return redirect()->route('admin.articles.index')->with('status', 'Article added successfully.');
    }

    public function edit(Article $article): View
    {
        return view('admin.articles.form', ['article' => $article]);
    }

    public function update(Request $request, Article $article, AdminFileStorage $files, AdminPanelNotifier $notifications): RedirectResponse
    {
        $data = $this->validated($request);
        $newCover = null;
        $oldCover = $article->cover_image;

        try {
            if ($request->hasFile('cover')) {
                $newCover = $files->store($request->file('cover'), 'covers/articles', 'public');
                $data['cover_image'] = $newCover;
            }
            unset($data['cover']);
            $article->update($data);
        } catch (Throwable $exception) {
            if ($newCover !== null) {
                try {
                    $files->delete($newCover, 'public');
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }

            throw $exception;
        }

        if ($article->wasChanged()) {
            $notifications->notifyChange($request, 'updated', 'Article', $article->title);
        }
        if ($newCover !== null) {
            $files->delete($oldCover, 'public');
        }

        return redirect()->route('admin.articles.index')->with('status', 'Article updated successfully.');
    }

    public function destroy(Request $request, Article $article, AdminFileStorage $files, AdminPanelNotifier $notifications): RedirectResponse
    {
        $cover = $article->cover_image;
        $title = $article->title;
        $article->delete();
        $notifications->notifyChange($request, 'deleted', 'Article', $title);
        $files->delete($cover, 'public');

        return redirect()->route('admin.articles.index')->with('status', 'Article deleted successfully.');
    }

    public function togglePublished(Request $request, Article $article, AdminPanelNotifier $notifications): RedirectResponse
    {
        $article->update(['is_published' => ! $article->is_published]);
        $notifications->notifyChange(
            $request,
            $article->is_published ? 'published' : 'unpublished',
            'Article',
            $article->title,
        );

        return back()->with('status', 'Article publication status updated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'content' => ['required', 'string', 'max:200000'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:'.config('admin.image_max_kb')],
        ]);
    }
}
