<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailChangeVerificationController;
use App\Http\Controllers\Admin\MagazineController;
use App\Http\Controllers\Admin\PasswordController;
use App\Http\Middleware\EncryptCookies;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::withoutMiddleware([
    StartSession::class,
    AddQueuedCookiesToResponse::class,
    EncryptCookies::class,
    ShareErrorsFromSession::class,
])->group(function (): void {
    Route::get('/', function () {
        return response()->json(['message' => 'Eruvaaka Backend API is running']);
    });
});

Route::prefix('admin')->name('admin.')->middleware('admin-session')->group(function (): void {
    Route::get('login', [AdminAuthController::class, 'create'])->name('login');
    Route::post('login', [AdminAuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    Route::get('email/verify/{adminUser}/{token}', EmailChangeVerificationController::class)
        ->middleware(['signed', 'throttle:10,1'])
        ->name('email.verify');

    Route::middleware('admin.auth')->group(function (): void {
        Route::get('/', fn () => redirect()->route('admin.dashboard'))->name('home');
        Route::post('logout', [AdminAuthController::class, 'destroy'])->name('logout');
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('password', [PasswordController::class, 'edit'])->name('password.edit');
        Route::put('password', [PasswordController::class, 'update'])->name('password.update');

        Route::get('books', [BookController::class, 'index'])->name('books.index');
        Route::get('books/create', [BookController::class, 'create'])->name('books.create');
        Route::post('books', [BookController::class, 'store'])->name('books.store');
        Route::get('books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
        Route::put('books/{book}', [BookController::class, 'update'])->name('books.update');
        Route::delete('books/{book}', [BookController::class, 'destroy'])->middleware('admin.role:super_admin')->name('books.destroy');
        Route::patch('books/{book}/publication', [BookController::class, 'togglePublished'])->middleware('admin.role:super_admin')->name('books.publication');

        Route::get('magazines', [MagazineController::class, 'index'])->name('magazines.index');
        Route::get('magazines/create', [MagazineController::class, 'create'])->name('magazines.create');
        Route::post('magazines', [MagazineController::class, 'store'])->name('magazines.store');
        Route::get('magazines/{magazine}/edit', [MagazineController::class, 'edit'])->name('magazines.edit');
        Route::put('magazines/{magazine}', [MagazineController::class, 'update'])->name('magazines.update');
        Route::delete('magazines/{magazine}', [MagazineController::class, 'destroy'])->middleware('admin.role:super_admin')->name('magazines.destroy');
        Route::patch('magazines/{magazine}/publication', [MagazineController::class, 'togglePublished'])->middleware('admin.role:super_admin')->name('magazines.publication');

        Route::get('articles', [ArticleController::class, 'index'])->name('articles.index');
        Route::get('articles/create', [ArticleController::class, 'create'])->name('articles.create');
        Route::post('articles', [ArticleController::class, 'store'])->name('articles.store');
        Route::get('articles/{article}/edit', [ArticleController::class, 'edit'])->name('articles.edit');
        Route::put('articles/{article}', [ArticleController::class, 'update'])->name('articles.update');
        Route::delete('articles/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');
        Route::patch('articles/{article}/publication', [ArticleController::class, 'togglePublished'])->name('articles.publication');

        Route::middleware('admin.role:super_admin')->prefix('users')->name('users.')->group(function (): void {
            Route::get('/', [AdminUserController::class, 'index'])->name('index');
            Route::get('create', [AdminUserController::class, 'create'])->name('create');
            Route::post('/', [AdminUserController::class, 'store'])->name('store');
            Route::get('{adminUser}/edit', [AdminUserController::class, 'edit'])->name('edit');
            Route::put('{adminUser}', [AdminUserController::class, 'update'])->name('update');
            Route::delete('{adminUser}', [AdminUserController::class, 'destroy'])->name('destroy');
        });
    });
});

require __DIR__.'/legacy.php';
