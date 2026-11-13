@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1">Dashboard</h1>
        <p class="text-secondary mb-0">Manage Eruvaaka publications and agriculture content.</p>
    </div>
    <div class="row g-3 mb-4">
        @foreach ([['Books', $bookCount], ['Magazines', $magazineCount], ['Articles', $articleCount], ['Published content', $publishedCount]] as [$label, $count])
            <div class="col-6 col-xl-3">
                <div class="card content-card h-100"><div class="card-body">
                    <div class="small text-secondary">{{ $label }}</div>
                    <div class="fs-2 fw-semibold">{{ $count }}</div>
                </div></div>
            </div>
        @endforeach
    </div>
    <div class="card content-card">
        <div class="card-header bg-white py-3"><h2 class="h5 mb-0">Recent uploads</h2></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Title</th><th>Content type</th><th>Uploaded</th><th></th></tr></thead>
                <tbody>
                @forelse ($recentUploads as $upload)
                    <tr><td>{{ $upload['title'] }}</td><td>{{ $upload['type'] }}</td><td>{{ $upload['created_at']?->format('d M Y') }}</td><td><a href="{{ $upload['url'] }}">Edit</a></td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-secondary py-4">No uploads yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
