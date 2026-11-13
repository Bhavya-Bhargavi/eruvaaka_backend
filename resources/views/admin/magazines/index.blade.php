@extends('admin.layout')

@section('title', 'Magazines')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div><h1 class="h3 mb-1">Magazines</h1><p class="text-secondary mb-0">Manage monthly magazine issues and PDF files.</p></div>
        <a class="btn btn-success" href="{{ route('admin.magazines.create') }}">Add magazine</a>
    </div>
    <div class="card content-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Title</th><th>Issue</th><th>Status</th><th>Uploaded</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse ($magazines as $magazine)
                    <tr>
                        <td>{{ $magazine->title }}</td><td>{{ $magazine->month ? \Carbon\Carbon::create()->month($magazine->month)->format('F') : '—' }} {{ $magazine->year }}</td>
                        <td><span class="badge text-bg-{{ $magazine->is_available ? 'success' : 'secondary' }}">{{ $magazine->is_available ? 'Published' : 'Draft' }}</span></td>
                        <td>{{ $magazine->created_at ? $magazine->created_at->format('d M Y') : '—' }}</td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.magazines.edit', $magazine) }}">Edit</a>
                            @if (auth('admin')->user()->role === 'super_admin')
                                <form class="d-inline" method="post" action="{{ route('admin.magazines.publication', $magazine) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success" type="submit">{{ $magazine->is_available ? 'Unpublish' : 'Publish' }}</button></form>
                                <form class="d-inline" method="post" action="{{ route('admin.magazines.destroy', $magazine) }}" onsubmit="return confirm('Delete this magazine and its uploaded files?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Delete</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-4">No magazines have been added.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $magazines->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection
