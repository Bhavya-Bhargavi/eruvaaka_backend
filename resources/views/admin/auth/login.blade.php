<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login | Eruvaaka</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="card border-0 shadow-sm w-100" style="max-width: 430px">
        <div class="card-body p-4 p-md-5">
            <h1 class="h3 mb-1">Eruvaaka Admin</h1>
            <p class="text-secondary mb-4">Sign in to manage publications and agriculture content.</p>
            @if ($errors->any())
                <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
            @endif
            <form method="post" action="{{ route('admin.login.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">Email address</label>
                    <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" id="password" name="password" type="password" required autocomplete="current-password">
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" id="remember" name="remember" type="checkbox" value="1">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <button class="btn btn-success w-100" type="submit">Sign in</button>
            </form>
        </div>
    </div>
</main>
</body>
</html>
