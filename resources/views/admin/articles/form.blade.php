@extends('admin.layout')

@section('title', $article->exists ? 'Edit article' : 'Add article')

@section('content')
    <div class="mb-4"><h1 class="h3 mb-1">{{ $article->exists ? 'Edit article' : 'Add article' }}</h1><p class="text-secondary mb-0">New articles start as drafts. Use the list to publish them.</p></div>
    <form class="card content-card" method="post" enctype="multipart/form-data" action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}">
        @csrf
        @if ($article->exists) @method('PUT') @endif
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-8"><label class="form-label" for="title">Article title *</label><input class="form-control" id="title" name="title" value="{{ old('title', $article->title) }}" required maxlength="255"></div>
                <div class="col-md-4"><label class="form-label" for="category">Category</label><input class="form-control" id="category" name="category" value="{{ old('category', $article->category) }}" maxlength="255"></div>
                <div class="col-12"><label class="form-label" for="description">Short description</label><textarea class="form-control" id="description" name="description" rows="2">{{ old('description', $article->description) }}</textarea></div>
                <div class="col-12"><label class="form-label" for="content">Article content *</label><textarea class="form-control" id="content" name="content" rows="10" required>{{ old('content', $article->content) }}</textarea></div>
                <div class="col-md-6"><label class="form-label" for="cover">Cover image</label><input class="form-control" id="cover" name="cover" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png"></div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2"><button class="btn btn-success" type="submit">Save article</button><a class="btn btn-outline-secondary" href="{{ route('admin.articles.index') }}">Cancel</a></div>
    </form>
@endsection
