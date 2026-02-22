<?php

namespace App\Http\Controllers\connection;

use App\Http\Controllers\Controller;
use App\Http\Requests\Connection\UpdateRequest;
use Illuminate\Http\Request;

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
        return response()->json(['message' => "AccountController update", 'user' => $user]);
    }
}
