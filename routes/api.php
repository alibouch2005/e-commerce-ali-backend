<?php

use App\Http\Controllers\auth\LoginController;
use App\Http\Controllers\auth\LogoutController;
use App\Http\Controllers\auth\RegisterController;
use App\Http\Controllers\connection\AccountController;
use App\Http\Controllers\tables\ProductController;
use App\Http\Controllers\tables\CategoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Affichage des formulaires d'authentification
Route::get('/login', [LoginController::class, 'showForm'])->name('login');
Route::get('/register', [RegisterController::class, 'showForm'])->name('register');

// Traitement des authentifications
Route::post('/login', [LoginController::class, 'login'])->middleware('guest');
Route::post('/register', [RegisterController::class, 'register'])->middleware('guest');

/*|--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------*/

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('/user')->controller(AccountController::class)->group(function () {
        Route::get('', 'show');
        Route::put('', 'update');
        Route::delete('/delete', 'delete');
        Route::patch('/change-password', 'changePassword');
    });

    /*|--------------------------------------------------------------------------
    | Logout Route
    |--------------------------------------------------------------------------*/

    // Route pour la déconnexion, accessible à tous les utilisateurs authentifiés
    Route::post('/logout', [LogoutController::class, 'logout']);

    /*|--------------------------------------------------------------------------
    | Category Routes
    |--------------------------------------------------------------------------*/

    // Routes accessibles à tous les utilisateurs authentifiés
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);

    // Routes admin uniquement
    Route::middleware('role:admin')->group(function () {
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    });

    /*|--------------------------------------------------------------------------
    | Product Routes
    |--------------------------------------------------------------------------*/

    // Routes accessibles à tous les utilisateurs authentifiés

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);

    // Routes admin uniquement

    Route::middleware('role:admin')->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    });

});
