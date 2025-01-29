<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\LogUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Exception;

class AuthController extends Controller
{
    public function login(LogUserRequest $request)
    {
     
        try {
          
            
            $user = User::where('email', $request->email)->first();
    
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status_code' => 401,
                    'message' => 'Le mot de passe fournit ne correspond à aucun compte.',
                ], 401);
            }
    
            // Vérification de l'état du compte
            if (!$user->status) {
                return response()->json([
                    'status_code' => 401,
                    'message' => 'Votre compte est désactivé. Veuillez contacter l\'administrateur.',
                ], 401);
            }
    
            // Création du token d'authentification
            $token = $user->createToken('auth_token')->plainTextToken;
    
            return response()->json([
                'status_code' => 200,
                'message' => 'Utilisateur connecté avec succès.',
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);
        } catch (Exception $e) {
            // Gestion des erreurs
            return response()->json([
                'status_code' => 500,
                'message' => 'Echec lors de la connexion!',
                'error' => $e->getMessage()
            ], 500);
        }
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
