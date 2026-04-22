<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(LoginRequest $request)
    {
        // Récupère uniquement les champs email et password de la requête HTTP.
        $credentials = $request->only('email', 'password');

        // Tente de connecter l'utilisateur avec les informations d'identification fournies
        if (Auth::attempt($credentials)) {
            // Si la connexion est réussie, retourne les informations de l'utilisateur connecté
            $request->session()->regenerate(); // Regénère la session pour éviter les attaques de fixation de session
            return response()->json(['user' => Auth::user()], 200);
        }
        // Si la connexion échoue, retourne une réponse d'erreur avec un message approprié
        return response()->json(['message' => 'Invalid credentials'], 401);
    }
}
