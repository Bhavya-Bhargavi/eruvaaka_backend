@extends('admin.layout')

@section('title', 'Change password')

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1">Change password</h1>
        <p class="text-secondary mb-0">Use a unique password with at least 12 characters.</p>
    </div>
    <form class="card content-card" method="post" action="{{ route('admin.password.update') }}">
        @csrf
        @method('PUT')
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="current_password">Current password *</label>
                    <input class="form-control" id="current_password" name="current_password" type="password" required autocomplete="current-password">
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="password">New password *</label>
                    <input class="form-control" id="password" name="password" type="password" required minlength="12" autocomplete="new-password">
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="password_confirmation">Confirm new password *</label>
                    <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" required minlength="12" autocomplete="new-password">
                </div>
            </div>
        </div>
        <div class="card-footer bg-white">
            <button class="btn btn-success" type="submit">Update password</button>
        </div>
    </form>
@endsection
