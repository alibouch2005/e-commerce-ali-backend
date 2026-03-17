<?php

namespace App\Http\Controllers\Connection;

use App\Http\Controllers\Controller;
use App\Http\Requests\Connection\ChangePasswordRequest;
use App\Http\Requests\Connection\UpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    // Afficher le profil
    public function show(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }

    // Mettre à jour le profil
    public function update(UpdateRequest $request)
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json([
            'message' => 'Account updated successfully',
            'user' => $user
        ]);
    }

    // Supprimer le compte
    public function delete(Request $request)
    {
        $user = $request->user();

        // Supprimer tous les tokens
        $user->tokens()->delete();

        // Supprimer utilisateur
        $user->delete();

        return response()->json([
            'message' => 'Account deleted successfully'
        ]);
    }

    // Changer mot de passe
    public function changePassword(ChangePasswordRequest $request)
{
    $user = $request->user();

    // Vérifier l'ancien mot de passe
    if (!Hash::check($request->current_password, $user->password)) {
        return response()->json([
            'message' => 'Current password incorrect'
        ], 422);
    }

    // Mettre à jour le nouveau mot de passe
    $user->update([
        'password' => Hash::make($request->new_password)
    ]);

    

    return response()->json([
        'message' => 'Password changed successfully'
    ]);
}
}