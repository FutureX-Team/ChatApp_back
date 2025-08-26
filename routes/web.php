<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\UserRegisterController;
use App\Http\Controllers\UserLoginController;
use App\Http\Controllers\Auth\GoogleController;

Route::get('/auth/google/redirect', [GoogleController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleController::class, 'callback']);
