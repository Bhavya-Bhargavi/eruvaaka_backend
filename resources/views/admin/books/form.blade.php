@extends('admin.layout')

@section('title', $book->exists ? 'Edit book' : 'Add book')

@section('content')
    <div class="mb-4"><h1 class="h3 mb-1">{{ $book->exists ? 'Edit book' : 'Add book' }}</h1><p class="text-secondary mb-0">PDF files are stored privately and only downloadable when published.</p></div>
    <form class="card content-card" method="post" enctype="multipart/form-data" action="{{ $book->exists ? route('admin.books.update', $book) : route('admin.books.store') }}">
        @csrf
        @if ($book->exists) @method('PUT') @endif
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-8"><label class="form-label" for="title">Book title *</label><input class="form-control" id="title" name="title" value="{{ old('title', $book->title) }}" required maxlength="255"></div>
                <div class="col-md-4"><label class="form-label" for="author">Author</label><input class="form-control" id="author" name="author" value="{{ old('author', $book->author) }}" maxlength="255"></div>
                <div class="col-md-6"><label class="form-label" for="category">Category</label><input class="form-control" id="category" name="category" value="{{ old('category', $book->category) }}" maxlength="255"></div>
                <div class="col-12"><label class="form-label" for="description">Description</label><textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $book->description) }}</textarea></div>
                <div class="col-12"><label class="form-label" for="content">Reader text (optional)</label><textarea class="form-control" id="content" name="content" rows="4">{{ old('content', $book->content) }}</textarea><div class="form-text">The existing Android book-detail API continues to return this text.</div></div>
                <div class="col-md-6"><label class="form-label" for="pdf">PDF {{ $book->exists ? '(leave empty to keep current)' : '*' }}</label><input class="form-control" id="pdf" name="pdf" type="file" accept="application/pdf" {{ $book->exists ? '' : 'required' }}><div class="form-text">PDF only; maximum {{ number_format(config('admin.pdf_max_kb') / 1024, 0) }} MB.</div></div>
                <div class="col-md-6"><label class="form-label" for="cover">Cover image</label><input class="form-control" id="cover" name="cover" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png"><div class="form-text">JPG or PNG; maximum {{ number_format(config('admin.image_max_kb') / 1024, 0) }} MB.</div></div>
                @if ($book->pdf_path)<div class="col-12"><div class="form-text">A PDF is currently uploaded.</div></div>@endif
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2"><button class="btn btn-success" type="submit">Save book</button><a class="btn btn-outline-secondary" href="{{ route('admin.books.index') }}">Cancel</a></div>
    </form>
@endsection
