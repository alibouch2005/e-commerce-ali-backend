<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\auth\LogoutRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function logout(LogoutRequest $request)
    {
        // Vérifie si l'utilisateur est authentifié avant de tenter de le déconnecter
        if (Auth::guard('web')->check()) {

            // Déconnecte l'utilisateur en utilisant le guard 'web'
            Auth::guard('web')->logout();
            $request->session()->invalidate(); // Invalide la session de l'utilisateur
            $request->session()->regenerateToken(); // Régénère le token CSRF pour éviter les attaques CSRF après déconnexion.
            // Retourne une réponse de succès indiquant que la déconnexion a réussi
            return response()->json(['message' => 'Successfully logged out'], 200);
        }
        return response()->json(['message' => 'Unauthorized'], 401);  
    }
}
