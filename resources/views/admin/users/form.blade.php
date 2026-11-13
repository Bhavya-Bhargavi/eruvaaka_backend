@extends('admin.layout')

@section('title', $adminUser->exists ? 'Edit admin user' : 'Add admin user')

@section('content')
    <div class="mb-4"><h1 class="h3 mb-1">{{ $adminUser->exists ? 'Edit admin user' : 'Add admin user' }}</h1><p class="text-secondary mb-0">Passwords must be at least 12 characters.</p></div>
    @if ($adminUser->pending_email)
        <div class="alert alert-warning" role="alert">
            Email change to <strong>{{ $adminUser->pending_email }}</strong> is awaiting verification. The current email remains active until verified.
        </div>
    @endif
    <form class="card content-card" method="post" action="{{ $adminUser->exists ? route('admin.users.update', $adminUser) : route('admin.users.store') }}">
        @csrf
        @if ($adminUser->exists) @method('PUT') @endif
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label" for="name">Name *</label><input class="form-control" id="name" name="name" value="{{ old('name', $adminUser->name) }}" required maxlength="255"></div>
                <div class="col-md-6"><label class="form-label" for="email">Email *</label><input class="form-control" id="email" name="email" type="email" value="{{ old('email', $adminUser->email) }}" required maxlength="255"><div class="form-text">A change takes effect after the new address is verified.</div></div>
                <div class="col-md-6"><label class="form-label" for="password">Password {{ $adminUser->exists ? '(leave empty to keep current)' : '*' }}</label><input class="form-control" id="password" name="password" type="password" {{ $adminUser->exists ? '' : 'required' }} minlength="12" autocomplete="new-password"></div>
                <div class="col-md-6"><label class="form-label" for="password_confirmation">Confirm password</label><input class="form-control" id="password_confirmation" name="password_confirmation" type="password" {{ $adminUser->exists ? '' : 'required' }} minlength="12" autocomplete="new-password"></div>
                <div class="col-md-6"><label class="form-label" for="role">Role *</label><select class="form-select" id="role" name="role" required><option value="content_manager" @selected(old('role', $adminUser->role ?: 'content_manager') === 'content_manager')>Content Manager</option><option value="super_admin" @selected(old('role', $adminUser->role) === 'super_admin')>Super Admin</option></select></div>
                <div class="col-md-6 d-flex align-items-end"><div class="form-check mb-2"><input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $adminUser->exists ? $adminUser->is_active : true))><label class="form-check-label" for="is_active">Account active</label></div></div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2"><button class="btn btn-success" type="submit">Save admin user</button><a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">Cancel</a></div>
    </form>
@endsection
