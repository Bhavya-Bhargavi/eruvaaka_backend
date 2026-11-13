@extends('admin.layout')

@section('title', $magazine->exists ? 'Edit magazine' : 'Add magazine')

@section('content')
    <div class="mb-4"><h1 class="h3 mb-1">{{ $magazine->exists ? 'Edit magazine' : 'Add magazine' }}</h1><p class="text-secondary mb-0">Magazine PDFs are kept in private storage. Duplicate month/year issues are allowed.</p></div>
    <form class="card content-card" method="post" enctype="multipart/form-data" action="{{ $magazine->exists ? route('admin.magazines.update', $magazine) : route('admin.magazines.store') }}">
        @csrf
        @if ($magazine->exists) @method('PUT') @endif
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-8"><label class="form-label" for="title">Magazine title *</label><input class="form-control" id="title" name="title" value="{{ old('title', $magazine->title) }}" required maxlength="255"></div>
                <div class="col-md-2"><label class="form-label" for="month">Month *</label><select class="form-select" id="month" name="month" required><option value="">Select</option>@foreach (range(1, 12) as $month)<option value="{{ $month }}" @selected((int) old('month', $magazine->month) === $month)>{{ \Carbon\Carbon::create()->month($month)->format('F') }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label" for="year">Year *</label><input class="form-control" id="year" name="year" type="number" min="2000" max="2100" value="{{ old('year', $magazine->year ?: now()->year) }}" required></div>
                <div class="col-12"><label class="form-label" for="description">Description</label><textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $magazine->description) }}</textarea></div>
                <div class="col-md-6"><label class="form-label" for="pdf">Magazine PDF {{ $magazine->exists ? '(leave empty to keep current)' : '*' }}</label><input class="form-control" id="pdf" name="pdf" type="file" accept="application/pdf" {{ $magazine->exists ? '' : 'required' }}></div>
                <div class="col-md-6"><label class="form-label" for="cover">Cover image</label><input class="form-control" id="cover" name="cover" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png"></div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2"><button class="btn btn-success" type="submit">Save magazine</button><a class="btn btn-outline-secondary" href="{{ route('admin.magazines.index') }}">Cancel</a></div>
    </form>
@endsection
