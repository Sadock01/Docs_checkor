<?php

namespace App\Http\Controllers;

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

    public function create(Request $request)
    {
        $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

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

    public function update(Request $request, User $user)
    {
        $request->validate([
            'firstname' => 'sometimes|string|max:255',
            'lastname' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|min:8',
            'status' => 'sometimes|boolean',
            'role_id' => 'sometimes|exists:roles,id',
        ]);

        try {
            $data = $request->only(['firstname', 'lastname', 'email', 'password', 'status', 'role_id']);

            if (isset($data['password'])) {
                $data['password'] = bcrypt($data['password']);
            }

            $user->update($data);

            return response()->json([
                'status_code' => 200,
                'status_message' => 'Utilisateur mis à jour avec succès.',
                'user' => [
                    'id' => $user->id,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'email' => $user->email,
                    'status' => $user->status,
                    'role_id' => $user->role_id,
                ],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 401,
                'message' => 'Erreur survenue lors de la mise à jour de l\'utilisateur.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
