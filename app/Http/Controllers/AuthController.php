<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\LogUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;


class AuthController extends Controller
{
    public function login(LogUserRequest $request)
    {
        
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return [
                'status_code' => 401,
                'message' => 'Le mot de passe fournit ne correspond à aucun compte.',
            ];
        }


        // Vérifier si le compte est désactivé
       
        if (!$user->status) {
          
            return response()->json([
                'status_code' => 401,
                'message' => 'Votre compte est désactivé. Veuillez contacter l\'administrateur.',
            ], 401);
        }
        

        $token = $user->createToken('auth_token')->plainTextToken;


        return [
            'status_code' => 200,
            'status_message' => 'Utilisateur connecté avec succès.',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',

        ];
    }

    public function checkUserActivity()
{
    $user = Auth::user();
    $inactiveTime = now()->diffInMinutes($user->last_activity);

    if ($inactiveTime > 30) { // Exemple : 15 minutes d'inactivité
        Auth::logout(); // Déconnecter l'utilisateur
        return response()->json([
            'message' => 'Votre session a expiré en raison d\'une inactivité prolongée.'
        ], 401);
    }

    return response()->json(['message' => 'Session active.']);
}

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return [
            'status_code' => 200,
            'message' => 'Utilisateur déconnecté avec succès.'
        ];
    }
}
