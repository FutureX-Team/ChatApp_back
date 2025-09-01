<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\UserRegisterController;
use App\Http\Controllers\UserLoginController;
use App\Http\Controllers\Auth\GoogleController;


// This file is intentionally left empty as all API routes are defined in routes/api.php
Route::get('/', function () {
    return view('welcome');
});


Route::get('/auth/google', [GoogleController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleController::class, 'callback']);
