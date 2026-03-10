<?php

use App\Http\Controllers\Catalogue\CategorieController;
use App\Http\Controllers\Catalogue\ProductController;
use App\Http\Controllers\Catalogue\CartController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Catalogue Routes
|--------------------------------------------------------------------------
|
| Routes pour le catalogue public : catégories, produits visibles sans authentification.
| Pour le panier, ajout nécessite authentification.
|
*/

// Routes publiques pour les catégories
Route::prefix('categories')->controller(CategorieController::class)->group(function () {
    Route::get('/', 'index'); // Liste des catégories
    Route::get('/{category}', 'show'); // Détails d'une catégorie
});

// Routes publiques pour les produits
Route::prefix('products')->controller(ProductController::class)->group(function () {
   
    Route::get('/', 'index'); // Liste des produits (avec filtre par catégorie optionnel)
    Route::get('/{product}', 'show'); // Détails d'un produit
});

// Routes pour le panier (ajout nécessite authentification, géré dans le contrôleur)
Route::prefix('cart')->controller(CartController::class)->group(function () {
    Route::post('/add', 'add'); // Ajouter au panier (auth required)
    Route::get('/', 'index'); // Voir le panier (auth required)
    Route::put('/update-quantity/{cartItem}', 'updateQuantity'); // Mettre à jour quantité (auth required)
    Route::delete('/remove/{cartItem}', 'remove'); // Supprimer item (auth required)
    Route::delete('/clear', 'clear'); // Vider le panier (auth required)
});