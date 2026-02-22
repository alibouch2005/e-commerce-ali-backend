<?php

use App\Http\Controllers\auth\LoginController;
use App\Http\Controllers\auth\LogoutController;
use App\Http\Controllers\auth\RegisterController;
use App\Http\Controllers\connection\AccountController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('/login', [LoginController::class, 'login'])->middleware('guest');
Route::post('/register', [RegisterController::class, 'register'])->middleware('guest');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('/user')->controller(AccountController::class)->group(function () {
        Route::get('', 'show');
        Route::put('', 'update');
    });
    Route::post('/logout', [LogoutController::class, 'logout']);
});
