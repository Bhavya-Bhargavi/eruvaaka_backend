<?php

use App\Http\Controllers\Api\PublishedContentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('news', [NewsController::class, 'index']);
Route::get('products', [ProductController::class, 'index']);
Route::get('offers/active', [OfferController::class, 'active']);
Route::get('jobs', [JobController::class, 'index']);
Route::get('jobs/{jobId}', [JobController::class, 'show'])->whereNumber('jobId');
Route::post('contact', [ContactController::class, 'store']);
Route::get('books', [ContentController::class, 'books']);
Route::get('books/{bookId}', [ContentController::class, 'book'])->middleware('legacy.auth:optional');
Route::get('forums', [ContentController::class, 'forums']);
Route::get('forums/{forumId}', [ContentController::class, 'forum']);
Route::get('forums/{forumId}/comments', [ContentController::class, 'comments']);
Route::get('epapers', [ContentController::class, 'epapers']);
Route::get('emagazines', [ContentController::class, 'emagazines']);
Route::get('epapers/{publicationId}/download', [ContentController::class, 'downloadEpaper'])->whereNumber('publicationId');
Route::get('emagazines/{publicationId}/download', [ContentController::class, 'downloadEmagazine'])->whereNumber('publicationId');

Route::prefix('auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('legacy.auth');
});

Route::prefix('v2')->name('api.v2.')->group(function (): void {
    Route::get('books', [PublishedContentController::class, 'books'])->name('books.index');
    Route::get('books/{book}', [PublishedContentController::class, 'book'])->middleware('legacy.auth:optional')->name('books.show');
    Route::get('books/{book}/download', [PublishedContentController::class, 'downloadBook'])->middleware('legacy.auth:optional')->name('books.download');
    Route::get('magazines', [PublishedContentController::class, 'magazines'])->name('magazines.index');
    Route::get('magazines/{magazine}', [PublishedContentController::class, 'magazine'])->name('magazines.show');
    Route::get('magazines/{magazine}/download', [PublishedContentController::class, 'downloadMagazine'])->name('magazines.download');
    Route::get('articles', [PublishedContentController::class, 'articles'])->name('articles.index');
    Route::get('articles/{article}', [PublishedContentController::class, 'article'])->name('articles.show');
});

Route::middleware('legacy.auth')->group(function (): void {
    Route::post('jobs', [JobController::class, 'store'])->middleware('throttle:10,1');
    Route::get('users/profile', [AuthController::class, 'profile']);
    Route::post('users/profile', [AuthController::class, 'updateProfile']);
    Route::post('users/updateProfile', [AuthController::class, 'updateProfile']);
    Route::post('forums/{forumId}/comments', [ContentController::class, 'postComment'])->middleware('throttle:10,1');
    Route::post('payments/order', [PaymentController::class, 'createOrder']);
    Route::post('coupon/validate', [PaymentController::class, 'validateCoupon']);
    Route::post('payments/verify', [PaymentController::class, 'verifyPayment']);
    Route::post('create-order', [PaymentController::class, 'createOrder']);
    Route::post('verify-payment', [PaymentController::class, 'verifyPayment']);
    Route::get('subscription-status/{userId}', [PaymentController::class, 'subscriptionStatus']);
});
