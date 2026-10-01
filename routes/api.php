<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('news', [NewsController::class, 'index']);
Route::post('contact', [ContactController::class, 'store']);
Route::get('books', [ContentController::class, 'books']);
Route::get('books/{bookId}', [ContentController::class, 'book']);
Route::get('forums', [ContentController::class, 'forums']);
Route::get('forums/{forumId}', [ContentController::class, 'forum']);
Route::get('forums/{forumId}/comments', [ContentController::class, 'comments']);
Route::get('epapers', [ContentController::class, 'epapers']);
Route::get('emagazines', [ContentController::class, 'emagazines']);
Route::get('epapers/{publicationId}/download', [ContentController::class, 'downloadEpaper'])->middleware('legacy.auth');
Route::get('emagazines/{publicationId}/download', [ContentController::class, 'downloadEmagazine'])->middleware('legacy.auth');

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
    Route::post('forums/{forumId}/comments', [ContentController::class, 'postComment'])->middleware('throttle:10,1');
    Route::post('payments/order', [PaymentController::class, 'createOrder']);
    Route::post('payments/verify', [PaymentController::class, 'verifyPayment']);
    Route::post('create-order', [PaymentController::class, 'createOrder']);
    Route::post('verify-payment', [PaymentController::class, 'verifyPayment']);
    Route::get('subscription-status/{userId}', [PaymentController::class, 'subscriptionStatus']);
});
