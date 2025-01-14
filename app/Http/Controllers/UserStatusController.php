<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserStatusController extends Controller
{
        // Activer un utilisateur
        public function activate($id)
        {
            $user = User::findOrFail($id);
            $user->status = true; // Activer le compte
            $user->save();
    
            return response()->json([
                'success' => true,
                'message' =>  "Le compte utilisateur a été activé avec succès.",
                'status_code' => 200,
                'user_status' => $user->status, // État actuel (true ou false)
            ], 200); 
        }
    
        // Désactiver un utilisateur
        public function deactivate($id)
        {
            $user = User::findOrFail($id);
            $user->status = false; // Désactiver le compte
            $user->save();
    
           
        return response()->json([
            'success' => true,
            'message' =>  "Le compte utilisateur a été désactivé avec succès.",
            'status_code' => 200,
            'user_status' => $user->status, // État actuel (true ou false)
        ], 200);
        }
}
