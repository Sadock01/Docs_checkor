<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Exception;

class UserController extends Controller
{
    public function index(Request $request)
    {
        try{
        $query = User::query();
            $perPage = 5;
            $page = $request->input('page', 5);
            $search = $request->input('search');


            if ($search) {
                $query->whereRaw("identifier LIKE " % " . $search . " % "");
            }

            $total = $query->count();

            $result = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

            return response()->json([
                'status_code' => 200,
                'status_message' => 'The list of users retrieved',
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'items' => $result,
            ]);
        } catch (Exception $e) {

            return response()->json(['message' => 'Error retrieving users list', 'error' => $e->getMessage()], 500);
        }
    }

    public function create(Request $request)
    {
        $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'role' => 'required|exists:roles,name',
        ]);

        try {
            $user = User::create([
                'firstname' => $request->name,
                'lastname' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'role_id' => $request->role_id,
            ]);

            $user->assignRole($request->input('role'));

            return response()->json(['status_code' => 200,
            'message' => 'User created successfully', 'user' => $user], 201);
        } catch (Exception $e) {

            return response()->json(['message' => 'Error creating user', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'firstname' => 'sometimes|string|max:255',
            'lastname' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|min:8',
            'role_id' => 'sometimes|exists:roles,id',
        ]);

        try {

            $user->update($request->only([
                'firstname',
                'lastname',
                'email',
                'password',
                'role_id'
            ]));

            return response()->json(['message' => 'User updated successfully', 'user' => $user]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error updating user', 'error' => $e->getMessage()], 500);
        }
    }

    
    }

