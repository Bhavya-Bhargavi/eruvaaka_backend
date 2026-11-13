@extends('admin.layout')

@section('title', 'Admin Users')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div><h1 class="h3 mb-1">Admin Users</h1><p class="text-secondary mb-0">Manage Super Admin and Content Manager access.</p></div>
        <a class="btn btn-success" href="{{ route('admin.users.create') }}">Add admin user</a>
    </div>
    <div class="card content-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Account</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse ($admins as $admin)
                    <tr>
                        <td>{{ $admin->name }}</td><td>{{ $admin->email }}</td>
                        <td>{{ $admin->role === 'super_admin' ? 'Super Admin' : 'Content Manager' }}</td>
                        <td><span class="badge text-bg-{{ $admin->is_active ? 'success' : 'secondary' }}">{{ $admin->is_active ? 'Active' : 'Disabled' }}</span></td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.users.edit', $admin) }}">Edit</a>
                            <form class="d-inline" method="post" action="{{ route('admin.users.destroy', $admin) }}" onsubmit="return confirm('Delete this admin account?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit" @disabled($admin->is(auth('admin')->user()))>Delete</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-4">No admin accounts found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $admins->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection
