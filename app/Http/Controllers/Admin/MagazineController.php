<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Emagazine;
use App\Services\AdminFileStorage;
use App\Services\AdminPanelNotifier;
use App\Services\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Throwable;

class MagazineController extends Controller
{
    public function index(): View
    {
        return view('admin.magazines.index', ['magazines' => Emagazine::latest()->paginate(15)]);
    }

    public function create(): View
    {
        return view('admin.magazines.form', ['magazine' => new Emagazine]);
    }

    public function store(Request $request, AdminFileStorage $files, UniqueSlug $slugs, AdminPanelNotifier $notifications): RedirectResponse
    {
        $data = $this->validated($request, true);
        $stored = [];

        try {
            $data['file_path'] = $files->store($request->file('pdf'), 'magazines', 'local');
            $stored[] = [$data['file_path'], 'local'];
            if ($request->hasFile('cover')) {
                $data['cover_image'] = $files->store($request->file('cover'), 'covers/magazines', 'public');
                $stored[] = [$data['cover_image'], 'public'];
            }

            $data['issue_date'] = Carbon::createFromDate($data['year'], $data['month'], 1)->toDateString();
            $data['slug'] = $slugs->make(Emagazine::class, $data['title'].'-'.$data['year'].'-'.$data['month']);
            $data['is_available'] = false;
            unset($data['pdf'], $data['cover']);
            $magazine = Emagazine::create($data);
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

        $notifications->notifyChange($request, 'created as draft', 'Magazine', $magazine->title);

        return redirect()->route('admin.magazines.index')->with('status', 'Magazine added successfully.');
    }

    public function edit(Emagazine $magazine): View
    {
        return view('admin.magazines.form', ['magazine' => $magazine]);
    }

    public function update(Request $request, Emagazine $magazine, AdminFileStorage $files, AdminPanelNotifier $notifications): RedirectResponse
    {
        $data = $this->validated($request, false);
        $newFiles = [];
        $oldFiles = [];

        try {
            if ($request->hasFile('pdf')) {
                $newPath = $files->store($request->file('pdf'), 'magazines', 'local');
                $newFiles[] = [$newPath, 'local'];
                $oldFiles[] = [$magazine->file_path, 'local'];
                $data['file_path'] = $newPath;
            }
            if ($request->hasFile('cover')) {
                $newPath = $files->store($request->file('cover'), 'covers/magazines', 'public');
                $newFiles[] = [$newPath, 'public'];
                $oldFiles[] = [$magazine->cover_image, 'public'];
                $data['cover_image'] = $newPath;
            }

            $data['issue_date'] = Carbon::createFromDate($data['year'], $data['month'], 1)->toDateString();
            unset($data['pdf'], $data['cover']);
            $magazine->update($data);
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

        if ($magazine->wasChanged()) {
            $notifications->notifyChange($request, 'updated', 'Magazine', $magazine->title);
        }
        foreach ($oldFiles as [$path, $disk]) {
            $files->delete($path, $disk);
        }

        return redirect()->route('admin.magazines.index')->with('status', 'Magazine updated successfully.');
    }

    public function destroy(Request $request, Emagazine $magazine, AdminFileStorage $files, AdminPanelNotifier $notifications): RedirectResponse
    {
        $paths = [[$magazine->file_path, 'local'], [$magazine->cover_image, 'public']];
        $title = $magazine->title;
        $magazine->delete();
        $notifications->notifyChange($request, 'deleted', 'Magazine', $title);

        foreach ($paths as [$path, $disk]) {
            $files->delete($path, $disk);
        }

        return redirect()->route('admin.magazines.index')->with('status', 'Magazine deleted successfully.');
    }

    public function togglePublished(Request $request, Emagazine $magazine, AdminPanelNotifier $notifications): RedirectResponse
    {
        $magazine->update(['is_available' => ! $magazine->is_available]);
        $notifications->notifyChange(
            $request,
            $magazine->is_available ? 'published' : 'unpublished',
            'Magazine',
            $magazine->title,
        );

        return back()->with('status', 'Magazine publication status updated.');
    }

    private function validated(Request $request, bool $creating): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'pdf' => [$creating ? 'required' : 'nullable', 'file', 'mimes:pdf', 'max:'.config('admin.pdf_max_kb')],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:'.config('admin.image_max_kb')],
        ]);
    }
}
