<?php

namespace App\Http\Controllers;


use App\Http\Requests\UserRequest;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Exception;

class UserController extends Controller
{
    public function index(Request $request)
    {
        try {
           $query = User::select(
    'users.id',
    'users.firstname',
    'users.lastname',
    'users.email',
    'users.status',
    'users.role_id',
    'roles.name as role_name' // ✅ ajout du nom du rôle
)
->join('roles', 'users.role_id', '=', 'roles.id') // ✅ jointure
->orderBy('users.created_at', 'desc');

            $perPage = 10;
            $page = $request->input('page', 1);
            $search = $request->input('search');

            if ($search) {
                $query->whereRaw("identifier LIKE ?", ['%' . $search . '%']);
            }

           $total = $query->count();

            $result = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

            return response()->json([
                'status_code' => 200,
                'status_message' => 'Liste des utilisateurs récupérée avec succès.',
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'data' => $result->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'firstname' => $user->firstname,
                        'lastname' => $user->lastname,
                        'email' => $user->email,
                        'status' => $user->status,
                        'role_id' => $user->role_id,
                        'role_name' => $user->role_name

                    ];
                }),  
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 401,
                'message' => 'Erreur survenue lors de la récupération des utilisateurs.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

   

public function store(Request $request)
{
    try {

      $validated = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role_id' => 'required|exists:roles,id',
        ], [
            
            'firstname.required' => 'Le prénom est requis.',
            'lastname.required' => 'Le nom est requis.',
            'email.required' => 'L\'email est requis.',
            'email.email' => 'L\'email doit être une adresse valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'password.required' => 'Le mot de passe est requis.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'role_id.required' => 'Le rôle est requis.',
            'role_id.exists' => 'Le rôle sélectionné est invalide.',
        ]);
        $user = User::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'password' => $request->password,
            'status' => true,
            'role_id' => $request->role_id, // Tu peux stocker l’ID à part si tu veux
        ]);

        // Trouver le rôle par ID et assigner par son nom
        $role = Role::find($request->role_id);
        if (!$role) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Rôle introuvable.',
            ], 404);
        }

        $user->assignRole($role->name); // assignRole attend un nom

        return response()->json([
            'status_code' => 201,
            'message' => 'Utilisateur ajouté avec succès.',
            'user' => [
                'id' => $user->id,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'status' => $user->status,
                'role_id' => $user->role_id,
                'role_name' => $role->name,
            ],
        ], 201);
    } catch (Exception $e) {
        return response()->json([
            'status_code' => 500,
            'message' => 'Erreur survenue lors de la création de l\'utilisateur.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


  public function update(Request $request, $id)
{
    try {
        // Récupération de l'utilisateur à mettre à jour
        $user = User::findOrFail($id);

        // Validation des données entrantes
        $request->validate([
            'firstname' => 'sometimes|string|max:255',
            'lastname' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|min:8',
            'status' => 'sometimes|boolean',
            'role_id' => 'sometimes|exists:roles,id',
        ]);

        // Mise à jour des données utilisateur
        $user->update([
            'firstname' => $request->input('firstname', $user->firstname),
            'lastname' => $request->input('lastname', $user->lastname),
            'email' => $request->input('email', $user->email),
            'password' => $request->has('password') ? bcrypt($request->input('password')) : $user->password,
            'status' => $request->input('status', $user->status),
            'role_id' => $request->input('role_id', $user->role_id),
        ]);

        // 🔥 Charger la relation role pour avoir son nom
        $user->load('role');

        return response()->json([
            'status_code' => 200,
            'message' => 'Utilisateur mis à jour avec succès.',
            'data' => [
                'id' => $user->id,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'status' => $user->status,
                'role_id' => $user->role_id,
                'role_name' => $user->role ? $user->role->name : null, // ✅ plus null
            ],
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status_code' => 500,
            'message' => 'Erreur survenue lors de la mise à jour de l\'utilisateur.',
            'error' => $e->getMessage(),
        ], 500);
    }
}



}
