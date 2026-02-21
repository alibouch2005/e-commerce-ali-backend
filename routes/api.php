<?php

use App\Http\Controllers\auth\LoginController;
use App\Http\Controllers\auth\LogoutController;
use App\Http\Controllers\auth\RegisterController;
use Illuminate\Http\Request;
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

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [LogoutController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', function () {
            return response()->json(['message' => 'Admin access']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Livreur Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:livreur')->group(function () {
        Route::get('/livreur/orders', function () {
            return response()->json(['message' => 'Livreur access']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Admin + Livreur
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin,livreur')->group(function () {
        Route::get('/livraisons', function () {
            return response()->json(['message' => 'Gestion livraisons']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Client Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:client')->group(function () {
        Route::get('/client/profile', function () {
            return response()->json(['message' => 'Espace client']);
        });

        Route::get('/client/orders', function () {
            return response()->json(['message' => 'Mes commandes']);
        });
    });

});