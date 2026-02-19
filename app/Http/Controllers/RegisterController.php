<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $dataUser = $request->validated(); // Valide les données de la requête en utilisant les règles définies dans RegisterRequest
        $user = User::create($dataUser); // Crée un nouvel utilisateur dans la base de données avec les données validées
        Auth::login($user); // Connecte l'utilisateur après l'inscription
        return response()->json(['user' => $user], 201); // Retourne une réponse JSON avec les informations de l'utilisateur nouvellement créé et un code de statut 201 (Created)
    }
}
