<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserRegisterController;
use App\Http\Controllers\UserLoginController;
use Illuminate\Http\Request;

Route::post('/register', [UserRegisterController::class, 'register']);
Route::post('/login', [UserLoginController::class, 'login']);
Route::post('/logout', [UserLoginController::class, 'logout'])->middleware('auth:sanctum');
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
Route::get('/admin/dashboard', function () {
        return response()->json(['message' => 'Welcome Admin']);
    });
});
