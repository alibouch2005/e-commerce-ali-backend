<?php

use App\Http\Controllers\auth\LoginController;
use App\Http\Controllers\auth\LogoutController;
use App\Http\Controllers\RegisterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [LoginController::class, 'login'])->middleware('guest'); // Route pour la connexion des utilisateurs, accessible uniquement aux invités (non authentifiés)
Route::post('/register', [RegisterController::class, 'register'])->middleware('guest'); // Route pour l'inscription des utilisateurs, accessible uniquement aux invités (non authentifiés)
Route::middleware('auth:sanctum')->group(function () {
    // Routes protégées par l'authentification Sanctum, accessibles uniquement aux utilisateurs authentifiés
    Route::get('/user', function (Request $request) {
        return $request->user(); // Route pour récupérer les informations de l'utilisateur connecté
    });
    Route::post('/logout', [LogoutController::class, 'logout']); // Route pour la déconnexion des utilisateurs, accessible uniquement aux utilisateurs authentifiés
});

