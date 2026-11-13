@extends('admin.layout')

@section('title', 'Books')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div><h1 class="h3 mb-1">Books</h1><p class="text-secondary mb-0">Upload and maintain book details and PDF files.</p></div>
        <a class="btn btn-success" href="{{ route('admin.books.create') }}">Add book</a>
    </div>
    <div class="card content-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Title</th><th>Author</th><th>Category</th><th>Status</th><th>Uploaded</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse ($books as $book)
                    <tr>
                        <td>{{ $book->title }}</td><td>{{ $book->author ?: '—' }}</td><td>{{ $book->category ?: '—' }}</td>
                        <td><span class="badge text-bg-{{ $book->is_published ? 'success' : 'secondary' }}">{{ $book->is_published ? 'Published' : 'Draft' }}</span></td>
                        <td>{{ $book->created_at ? $book->created_at->format('d M Y') : '—' }}</td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.books.edit', $book) }}">Edit</a>
                            @if (auth('admin')->user()->role === 'super_admin')
                                <form class="d-inline" method="post" action="{{ route('admin.books.publication', $book) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success" type="submit">{{ $book->is_published ? 'Unpublish' : 'Publish' }}</button></form>
                                <form class="d-inline" method="post" action="{{ route('admin.books.destroy', $book) }}" onsubmit="return confirm('Delete this book and its uploaded files?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Delete</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-secondary py-4">No books have been added.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $books->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection
