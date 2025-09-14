<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    UserAuthController,
    TweetController,
    UserController,
    PlaceController,
    ReportController,
    AdminController,
    SupportTicketController,
    Auth\GoogleController,
    GuestAuthController
};





Route::get('/hi', fn() => 'Hello World')->middleware('throttle:30,1'); // NEW

/* ---------- Guest/Public ---------- */
// تسجيل/دخول — أشد تقييداً
Route::post('/register', [UserAuthController::class, 'register'])->middleware('throttle:6,1');  // TIGHTER
Route::post('/login',    [UserAuthController::class, 'login'])->middleware('throttle:9,1');    // TIGHTER

// OAuth Google — لا تُترك مفتوحة بدون حد
Route::get('/auth/google',            [GoogleController::class, 'redirect'])->middleware('throttle:10,1');   // NEW
Route::get('/auth/google/callback',   [GoogleController::class, 'callback'])->middleware('throttle:10,1');   // NEW

Route::post('/guest/ensure', [GuestAuthController::class, 'ensure'])->middleware('throttle:20,1');

Route::middleware('auth:sanctum')->post('/guest/logout', function (\Illuminate\Http\Request $r) {
    $r->user()->currentAccessToken()?->delete();
    return response()->noContent();
})->middleware('throttle:30,1');
// استعراض عام — حد معقول
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/tweets',        [TweetController::class, 'index']);
    Route::get('/tweets/filter', [TweetController::class, 'filter']);
    Route::get('/tweets/{id}',   [TweetController::class, 'show'])->whereNumber('id'); // NEW where
    Route::get('/users/{id}',        [UserController::class, 'show'])->whereNumber('id'); // NEW where
    Route::get('/users/{id}/tweets', [UserController::class, 'tweets'])->whereNumber('id'); // NEW where
    Route::get('/places', [PlaceController::class, 'index']);
});

/* ---------- Private (auth:sanctum + per-user limiter) ---------- */
Route::middleware(['auth:sanctum', 'throttle:per-user-10pm'])->group(function () {
    Route::post('/logout', [UserAuthController::class, 'logout'])->middleware('throttle:20,1'); // NEW

    // Support / Reports — شدّ إضافي لمنع الإغراق
    Route::post('/support', [SupportTicketController::class, 'store'])->middleware('throttle:8,1'); // TIGHTER
    Route::post('/reports', [ReportController::class, 'store'])->middleware('throttle:8,1');       // TIGHTER
    Route::get('/reports/mine', [ReportController::class, 'myReports'])->middleware('throttle:30,1'); // NEW

    // Me
    Route::get('/me', [UserController::class, 'me'])->middleware('throttle:60,1');     // NEW
    Route::put('/me', [UserController::class, 'updateMe'])->middleware('throttle:20,1'); // NEW

    // Tweets (كتابة/تعديل/تفاعل) — أشد تقييداً
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/tweets',                 [TweetController::class, 'store']);
        Route::delete('/tweets/{id}',          [TweetController::class, 'destroy'])->whereNumber('id'); // NEW where
        Route::post('/tweets/{id}/reply',      [TweetController::class, 'reply'])->whereNumber('id');   // NEW where
        Route::post('/tweets/{id}/like',       [TweetController::class, 'like'])->whereNumber('id');    // NEW where
        Route::post('/tweets/{id}/dislike',    [TweetController::class, 'dislike'])->whereNumber('id'); // NEW where
    });
});

/* ---------- Admin (auth + admin) ---------- */
Route::middleware(['auth:sanctum', 'admin', 'throttle:120,1'])->prefix('admin')->group(function () {
    Route::get('/support',   [SupportTicketController::class, 'index']);
    Route::get('/dashboard', [AdminController::class, 'stats'])->middleware('throttle:60,1'); // NEW
    Route::put('/users/{id}/disable', [AdminController::class, 'disable'])->whereNumber('id');
    Route::put('/users/{id}',         [AdminController::class, 'update'])->whereNumber('id');
    Route::delete('/tweets/{id}',     [AdminController::class, 'destroy'])->whereNumber('id');
    Route::get('/reports',            [AdminController::class, 'index'])->middleware('throttle:60,1'); // NEW
    Route::put('/reports/{id}',       [AdminController::class, 'updateReport'])->whereNumber('id');
});

/* ---------- Fallback ---------- */
Route::fallback(function () {
    return response()->json(['message' => 'Not Found'], 404);
});
