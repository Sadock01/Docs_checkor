<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\LogUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
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
                'message' => 'Connexion réussie.',
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
    // Récupération du token depuis l'en-tête Authorization
    $authHeader = $request->header('Authorization');

    if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
        return response()->json([
            'status_code' => 400,
            'message' => 'Token non fourni ou invalide.'
        ], 400);
    }

    $tokenValue = substr($authHeader, 7); // Supprime 'Bearer '

    // Recherche du token dans la base de données
    $token = PersonalAccessToken::findToken($tokenValue);

    if (!$token) {
        return response()->json([
            'status_code' => 404,
            'message' => 'Token non trouvé.'
        ], 404);
    }

    // Suppression du token
    $token->delete();

    return response()->json([
        'status_code' => 200,
        'message' => 'Déconnexion réussie.'
    ]);
}


public function me(Request $request)
{
    $userId = Auth::id();

    if (!$userId) {
        return response()->json([
            'status_code' => 401,
            'message' => 'Utilisateur non authentifié'
        ], 401);
    }

    $user = User::select(
        'users.id',
        'users.firstname',
        'users.lastname',
        'users.email',
        'users.status',
        'users.role_id',
        'roles.name as role_name'
    )
    ->join('roles', 'users.role_id', '=', 'roles.id')
    ->where('users.id', $userId)
    ->first();

    return response()->json([
        'status_code' => 200,
        'message' => 'Détails de l’utilisateur connecté',
        'data' => $user
    ]);
}




}
