<?php

use Illuminate\Support\Facades\Route;

Route::withoutMiddleware([
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \App\Http\Middleware\EncryptCookies::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
])->group(function (): void {
    Route::get('/', function () {
        return response()->json(['message' => 'Eruvaaka Backend API is running']);
    });
});


require __DIR__.'/legacy.php';
