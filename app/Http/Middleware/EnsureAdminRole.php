<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $admin = $request->user('admin');

        abort_unless($admin && in_array($admin->role, $roles, true), 403);

        return $next($request);
    }
}
