<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserAuthController;
use App\Http\Controllers\TweetController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\GoogleController;
Route::get('/hello', fn() => 'Hello World');

Route::post('/register', [UserAuthController::class, 'register']);
Route::post('/login', [UserAuthController::class, 'login']);

// Timeline & Tweets (عام)
Route::get('/tweets',        [TweetController::class, 'index']);
Route::get('/tweets/filter', [TweetController::class, 'filter']);
Route::get('/tweets/{id}',   [TweetController::class, 'show']);

// Users (عام)
Route::get('/users/{id}',        [UserController::class, 'show']);
Route::get('/users/{id}/tweets', [UserController::class, 'tweets']);

// Places
Route::get('/places', [PlaceController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Private (auth:sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [UserAuthController::class, 'logout']);

    // Me
    Route::get('/me', [UserController::class, 'me']);
    Route::put('/me', [UserController::class, 'updateMe']);

    // Tweets
    Route::post('/tweets',    [TweetController::class, 'store'])->middleware(['throttle:20,1']);
    Route::delete('/tweets/{id}', [TweetController::class, 'destroy']);
    Route::post('/tweets/{id}/reply',   [TweetController::class, 'reply']);
    Route::post('/tweets/{id}/like',    [TweetController::class, 'like']);
    Route::post('/tweets/{id}/dislike', [TweetController::class, 'dislike']);

    // Reports
    Route::post('/reports',     [ReportController::class, 'store']);
    Route::get('/reports/mine', [ReportController::class, 'myReports']);
});

/*
|--------------------------------------------------------------------------
| Admin (auth + role:admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'stats']);
    Route::put('/users/{id}/disable', [AdminController::class, 'disable']);
    Route::put('/users/{id}',         [AdminController::class, 'update']);
    Route::delete('/tweets/{id}',     [AdminController::class, 'destroy']);
    Route::get('/reports',            [AdminController::class, 'index']);
    Route::put('/reports/{id}', [AdminController::class, 'updateReport']);
});



Route::get('/auth/google', [GoogleController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleController::class, 'callback']);
