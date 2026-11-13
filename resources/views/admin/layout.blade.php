<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') | Eruvaaka</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7f5; }
        .admin-sidebar { min-height: 100vh; background: #173d31; }
        .admin-sidebar .nav-link { color: #dce8e1; border-radius: .5rem; }
        .admin-sidebar .nav-link.active, .admin-sidebar .nav-link:hover { color: #fff; background: #285d48; }
        .admin-brand { color: #fff; }
        .content-card { border: 0; box-shadow: 0 .125rem .5rem rgba(20, 45, 32, .06); }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <aside class="col-12 col-lg-2 p-3 admin-sidebar">
            <a class="d-block text-decoration-none fw-semibold fs-5 mb-4 admin-brand" href="{{ route('admin.dashboard') }}">Eruvaaka Admin</a>
            <nav class="nav nav-pills flex-column gap-1">
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
                <a class="nav-link {{ request()->routeIs('admin.books.*') ? 'active' : '' }}" href="{{ route('admin.books.index') }}">Books</a>
                <a class="nav-link {{ request()->routeIs('admin.magazines.*') ? 'active' : '' }}" href="{{ route('admin.magazines.index') }}">Magazines</a>
                <a class="nav-link {{ request()->routeIs('admin.articles.*') ? 'active' : '' }}" href="{{ route('admin.articles.index') }}">Articles</a>
                @if (auth('admin')->user()->role === 'super_admin')
                    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">Admin Users</a>
                @endif
                <a class="nav-link {{ request()->routeIs('admin.password.*') ? 'active' : '' }}" href="{{ route('admin.password.edit') }}">Change password</a>
            </nav>
            <div class="border-top border-success-subtle mt-4 pt-3">
                <div class="small text-white-50 mb-2">{{ auth('admin')->user()->name }}</div>
                <form method="post" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="btn btn-outline-light btn-sm w-100" type="submit">Logout</button>
                </form>
            </div>
        </aside>
        <main class="col-12 col-lg-10 px-3 px-md-4 py-4">
            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') }}
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('notification_warning'))
                <div class="alert alert-warning" role="alert">{{ session('notification_warning') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <strong>Please review the following:</strong>
                    <ul class="mb-0 mt-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
