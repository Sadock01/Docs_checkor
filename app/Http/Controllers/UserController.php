<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use Illuminate\Http\Request;
use App\Models\User;
use Exception;

class UserController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = User::query();
            $perPage = 5;
            $page = $request->input('page', 5);
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
                'items' => $result->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'firstname' => $user->firstname,
                        'lastname' => $user->lastname,
                        'email' => $user->email,
                        'status' => $user->status,
                        'role_id' => $user->role_id,
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

    public function store(UserRequest $request)
    {
        // La validation se fera automatiquement ici
        try {
            $user = User::create([
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'status' => true,
                'role_id' => $request->role_id,
            ]);

            $user->assignRole($request->role_id);

            return response()->json([
                'status_code' => 201,
                'status_message' => 'Utilisateur ajouté avec succès.',
                'user' => [
                    'id' => $user->id,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'email' => $user->email,
                    'status' => $user->status,
                    'role_id' => $user->role_id,
                ],
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 401,
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
                'firstname' => $request->input('firstname', $user->firstname), // Utilise la valeur existante si non fournie
                'lastname' => $request->input('lastname', $user->lastname),
                'email' => $request->input('email', $user->email),
                'password' => $request->has('password') ? bcrypt($request->input('password')) : $user->password,
                'status' => $request->input('status', $user->status),
                'role_id' => $request->input('role_id', $user->role_id),
            ]);

            // Mise à jour des rôles si nécessaire (si vous utilisez Spatie Role)
            // if ($request->has('role_id')) {
            //     $user->syncRoles([$request->input('role_id')]);
            // }

            return response()->json([
                'status_code' => 200,
                'status_message' => 'Utilisateur mis à jour avec succès.',
                'data' => $user,
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
