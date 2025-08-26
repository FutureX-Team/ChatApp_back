<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserRegisterController;
use App\Http\Controllers\UserLoginController;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\GoogleController;


Route::post('/register', [UserRegisterController::class, 'register']);
Route::post('/login', [UserLoginController::class, 'login']);
Route::post('/logout', [UserLoginController::class, 'logout'])->middleware('auth:sanctum');
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
Route::get('/admin/dashboard', function () {
        return response()->json(['message' => 'Welcome Admin']);
    });
});




Route::get('/me', function (Request $request) {
    return $request->user();                 // null if not logged in
})->middleware('auth:sanctum');

Route::post('/logout', function (Request $request) {
    auth()->guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return response()->json(['ok' => true]);
})->middleware('auth:sanctum');
