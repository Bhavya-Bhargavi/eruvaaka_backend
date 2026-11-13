<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Services\AdminFileStorage;
use App\Services\AdminPanelNotifier;
use App\Services\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class BookController extends Controller
{
    public function index(): View
    {
        return view('admin.books.index', ['books' => Book::latest()->paginate(15)]);
    }

    public function create(): View
    {
        return view('admin.books.form', ['book' => new Book]);
    }

    public function store(Request $request, AdminFileStorage $files, UniqueSlug $slugs, AdminPanelNotifier $notifications): RedirectResponse
    {
        $data = $this->validated($request, true);
        $stored = [];

        try {
            if ($request->hasFile('pdf')) {
                $data['pdf_path'] = $files->store($request->file('pdf'), 'books', 'local');
                $stored[] = [$data['pdf_path'], 'local'];
            }
            if ($request->hasFile('cover')) {
                $data['cover_image'] = $files->store($request->file('cover'), 'covers/books', 'public');
                $stored[] = [$data['cover_image'], 'public'];
            }

            $data['slug'] = $slugs->make(Book::class, $data['title']);
            $data['content'] = ($data['content'] ?? null) ?: (($data['description'] ?? null) ?: $data['title']);
            $data['is_published'] = false;
            unset($data['pdf'], $data['cover']);
            $book = Book::create($data);
        } catch (Throwable $exception) {
            foreach ($stored as [$path, $disk]) {
                try {
                    $files->delete($path, $disk);
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }

            throw $exception;
        }

        $notifications->notifyChange($request, 'created as draft', 'Book', $book->title);

        return redirect()->route('admin.books.index')->with('status', 'Book added successfully.');
    }

    public function edit(Book $book): View
    {
        return view('admin.books.form', ['book' => $book]);
    }

    public function update(Request $request, Book $book, AdminFileStorage $files, AdminPanelNotifier $notifications): RedirectResponse
    {
        $data = $this->validated($request, false);
        $newFiles = [];
        $oldFiles = [];

        try {
            if ($request->hasFile('pdf')) {
                $newPath = $files->store($request->file('pdf'), 'books', 'local');
                $newFiles[] = [$newPath, 'local'];
                $oldFiles[] = [$book->pdf_path, 'local'];
                $data['pdf_path'] = $newPath;
            }
            if ($request->hasFile('cover')) {
                $newPath = $files->store($request->file('cover'), 'covers/books', 'public');
                $newFiles[] = [$newPath, 'public'];
                $oldFiles[] = [$book->cover_image, 'public'];
                $data['cover_image'] = $newPath;
            }

            if (array_key_exists('content', $data) && ! $data['content']) {
                $data['content'] = ($data['description'] ?? null) ?: $data['title'];
            }
            unset($data['pdf'], $data['cover']);
            $book->update($data);
        } catch (Throwable $exception) {
            foreach ($newFiles as [$path, $disk]) {
                try {
                    $files->delete($path, $disk);
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }

            throw $exception;
        }

        if ($book->wasChanged()) {
            $notifications->notifyChange($request, 'updated', 'Book', $book->title);
        }
        foreach ($oldFiles as [$path, $disk]) {
            $files->delete($path, $disk);
        }

        return redirect()->route('admin.books.index')->with('status', 'Book updated successfully.');
    }

    public function destroy(Request $request, Book $book, AdminFileStorage $files, AdminPanelNotifier $notifications): RedirectResponse
    {
        $paths = [[$book->pdf_path, 'local'], [$book->cover_image, 'public']];
        $title = $book->title;
        $book->delete();
        $notifications->notifyChange($request, 'deleted', 'Book', $title);

        foreach ($paths as [$path, $disk]) {
            $files->delete($path, $disk);
        }

        return redirect()->route('admin.books.index')->with('status', 'Book deleted successfully.');
    }

    public function togglePublished(Request $request, Book $book, AdminPanelNotifier $notifications): RedirectResponse
    {
        $book->update(['is_published' => ! $book->is_published]);
        $notifications->notifyChange(
            $request,
            $book->is_published ? 'published' : 'unpublished',
            'Book',
            $book->title,
        );

        return back()->with('status', 'Book publication status updated.');
    }

    private function validated(Request $request, bool $creating): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'content' => ['nullable', 'string', 'max:200000'],
            'pdf' => [$creating ? 'required' : 'nullable', 'file', 'mimes:pdf', 'max:'.config('admin.pdf_max_kb')],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:'.config('admin.image_max_kb')],
        ]);
    }
}
