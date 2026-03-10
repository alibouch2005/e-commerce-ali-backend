<?php

use App\Http\Controllers\auth\LoginController;
use App\Http\Controllers\auth\LogoutController;
use App\Http\Controllers\auth\RegisterController;
use App\Http\Controllers\connection\AccountController;
use App\Http\Controllers\tables\CartController;
use App\Http\Controllers\tables\ProductController;
use App\Http\Controllers\tables\CategoryController;
use App\Http\Controllers\tables\DeliveryController;
use App\Http\Controllers\tables\OrderController;
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

/*
|--------------------------------------------------------------------------
| Public Catalogue Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/Catalogue.php';

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
    Route::prefix('admin/categories')->controller(CategoryController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{category}', 'show');
    });

    // Routes admin uniquement
    Route::middleware('role:admin')->group(function () {
        Route::post('/admin/categories', [CategoryController::class, 'store']);
        Route::put('/admin/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/admin/categories/{category}', [CategoryController::class, 'destroy']);
    });

    /*|--------------------------------------------------------------------------
    | Product Routes
    |--------------------------------------------------------------------------*/

    // Routes accessibles à tous les utilisateurs authentifiés

    Route::prefix('admin/products')->controller(ProductController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{product}', 'show');
    });

    // Routes admin uniquement

    Route::middleware('role:admin')->group(function () {
        Route::post('/admin/products', [ProductController::class, 'store']);
        Route::put('/admin/products/{product}', [ProductController::class, 'update']);
        Route::delete('/admin/products/{product}', [ProductController::class, 'destroy']);
    });
    /*|--------------------------------------------------------------------------
    | Cart Routes
    |--------------------------------------------------------------------------*/
    Route::middleware('role:client')->group(function () {
        // Routes pour la gestion du panier, accessibles à tous les utilisateurs authentifiés
        Route::prefix('/user/cart')->controller(CartController::class)->group(function () {
            Route::get('', 'index');
            Route::post('/add', 'add');
            Route::put('/update-quantity/{cartItem}', 'updateQuantity');
            Route::delete('/remove/{cartItem}', 'remove');
            Route::delete('/clear', 'clear');
        });
    });


    //    /*|--------------------------------------------------------------------------
    //    | Order Routes
    //    |--------------------------------------------------------------------------*/

    // Routes pour la gestion des commandes, accessibles à tous les utilisateurs authentifiés
    Route::middleware('role:client')->group(function () {

        Route::post('/checkout', [OrderController::class, 'checkout']);
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
    });

    // Routes admin uniquement pour la gestion des commandes
    Route::middleware('role:admin')->group(function () {

        Route::get('/admin/orders', [OrderController::class, 'adminIndex']);
        Route::get('/admin/orders/{order}', [OrderController::class, 'adminShow']);
        Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    });
    /*|--------------------------------------------------------------------------
    | Delivery Routes
    |--------------------------------------------------------------------------*/

    // Routes pour la gestion des livraisons, accessibles à tous les utilisateurs authentifiés

    Route::middleware('role:admin')->group(function () {

        Route::post('/deliveries/assign', [DeliveryController::class, 'assign']);
    });

    // Routes pour les livreurs uniquement pour la gestion de leurs livraisons
    Route::middleware('role:livreur')->group(function () {

        Route::get('/deliveries', [DeliveryController::class, 'myDeliveries']);
        Route::put('/deliveries/{delivery}/status', [DeliveryController::class, 'updateStatus']);
    });
});
