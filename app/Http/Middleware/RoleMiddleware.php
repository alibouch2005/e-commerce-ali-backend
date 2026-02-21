<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if(!Auth::check()){ // Vérifie si l'utilisateur est authentifié
            return response()->json(['message' => 'Unauthorized'], 401); // Retourne une réponse d'erreur si l'utilisateur n'est pas authentifié
        }
        if(!in_array(Auth::user()->role,$roles)){ // Vérifie si le rôle de l'utilisateur authentifié est dans la liste des rôles autorisés
            return response()->json(['message' => 'Unauthorized role'], 403); // Retourne une réponse d'erreur si le rôle de l'utilisateur n'est pas autorisé
        }
        return $next($request);
    }
}
