@extends('admin.layout')

@section('title', 'Articles')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div><h1 class="h3 mb-1">Agriculture articles</h1><p class="text-secondary mb-0">Create and publish local agriculture content.</p></div>
        <a class="btn btn-success" href="{{ route('admin.articles.create') }}">Add article</a>
    </div>
    <div class="card content-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse ($articles as $article)
                    <tr>
                        <td>{{ $article->title }}</td><td>{{ $article->category ?: '—' }}</td>
                        <td><span class="badge text-bg-{{ $article->is_published ? 'success' : 'secondary' }}">{{ $article->is_published ? 'Published' : 'Draft' }}</span></td>
                        <td>{{ $article->updated_at->format('d M Y') }}</td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.articles.edit', $article) }}">Edit</a>
                            <form class="d-inline" method="post" action="{{ route('admin.articles.publication', $article) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success" type="submit">{{ $article->is_published ? 'Unpublish' : 'Publish' }}</button></form>
                            <form class="d-inline" method="post" action="{{ route('admin.articles.destroy', $article) }}" onsubmit="return confirm('Delete this article?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Delete</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-4">No articles have been added.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $articles->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection
