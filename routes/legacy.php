<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::withoutMiddleware([
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \App\Http\Middleware\EncryptCookies::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
])->group(function (): void {
Route::prefix('android_api/api')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
        Route::post('resend-otp', [AuthController::class, 'resendOtp']);
        Route::post('logout', [AuthController::class, 'logout'])->middleware('legacy.auth');
    });
    Route::middleware('legacy.auth')->group(function (): void {
        Route::get('users/profile', [AuthController::class, 'profile']);
        Route::post('users/profile', [AuthController::class, 'updateProfile']);
        Route::post('users/updateProfile', [AuthController::class, 'updateProfile']);
        Route::post('payments/order', [PaymentController::class, 'createOrder']);
        Route::post('payments/verify', [PaymentController::class, 'verifyPayment']);
    });
});
});
