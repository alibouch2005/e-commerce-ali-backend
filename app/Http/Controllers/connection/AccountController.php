<?php

namespace App\Http\Controllers\connection;

use App\Http\Controllers\Controller;
use App\Http\Requests\Connection\ChangePasswordRequest;
use App\Http\Requests\Connection\UpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    
    function show(Request $request)
    {
        return response()->json(['User' => $request->user()]);//return response()->json(['message' => 'AccountController show']);

    }
    function update(UpdateRequest $request)
    {
        $user = $request->user(); // Récupère l'utilisateur actuellement authentifié à partir de la requête
        $data =$request->validated(); // Valide les données de la requête en utilisant les règles définies dans UpdateRequest
        $user->update($data); // Met à jour les informations de l'utilisateur dans la base de données avec les données validées
        return response()->json(['message' => "Account update", 'user' => $user]);
    }
    function delete(Request $request)
    {
        $user = $request->user(); // Récupère l'utilisateur actuellement authentifié à partir de la requête
        auth()->guard('web')->logout(); // Déconnecte l'utilisateur en utilisant le guard 'web'
        $user->delete(); // Supprime l'utilisateur de la base de données
        return response()->json(['message' => "Account delete"]);
    }
    function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user(); // Récupère l'utilisateur actuellement authentifié à partir de la requête
        $user->update(['password'=>Hash::make($request->new_password)]);// Met à jour le mot de passe de l'utilisateur dans la base de données avec le nouveau mot de passe haché
        return response()->json(['message' => "Password changed successfully"]);
    }
}
