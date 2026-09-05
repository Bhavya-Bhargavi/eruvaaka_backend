<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('legacy.auth');
});


Route::middleware('legacy.auth')->group(function (): void {
    Route::get('users/profile', [AuthController::class, 'profile']);
    Route::get('users/bookmarks', [AuthController::class, 'listBookmarks']);
    Route::post('users/bookmarks', [AuthController::class, 'addBookmark']);
    Route::delete('users/bookmarks/{article_slug}', [AuthController::class, 'removeBookmark']);
    Route::post('payments/order', [PaymentController::class, 'createOrder']);
    Route::post('payments/verify', [PaymentController::class, 'verifyPayment']);
    Route::post('create-order', [PaymentController::class, 'createOrder']);
    Route::post('verify-payment', [PaymentController::class, 'verifyPayment']);
    Route::get('subscription-status/{userId}', [PaymentController::class, 'subscriptionStatus']);
});
