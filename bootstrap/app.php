<?php

use App\Http\Middleware\AdminAuthenticate;
use App\Http\Middleware\EnsureAdminRole;
use App\Http\Middleware\LegacyJwtMiddleware;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            require __DIR__.'/../routes/web.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->group('stateless-web', []);
        $middleware->group('admin-session', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            PreventRequestForgery::class,
            SubstituteBindings::class,
        ]);
        $middleware->web(remove: [
            StartSession::class,
            AddQueuedCookiesToResponse::class,
            ShareErrorsFromSession::class,
            App\Http\Middleware\EncryptCookies::class,
        ]);
        $middleware->alias([
            'legacy.auth' => LegacyJwtMiddleware::class,
            'admin.auth' => AdminAuthenticate::class,
            'admin.role' => EnsureAdminRole::class,
        ]);
        $middleware->append(HandleCors::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
