<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    // Afficher le formulaire de demande de réinitialisation du mot de passe
    public function forgot(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        // Tente d'envoyer le lien de réinitialisation du mot de passe à l'adresse e-mail fournie
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => __($status)], 200);
        }
// Si l'envoi du lien de réinitialisation échoue, retourne une réponse d'erreur avec un message approprié
        return response()->json(['message' => __($status)], 400);
    }

    // Réinitialiser le mot de passe
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Mot de passe réinitialisé avec succès'], 200);
         }
        
    }
}