<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
class RoleController extends Controller
{
   

public function index()
{
    // Récupère tous les rôles
    $roles = Role::all();

    // Retourne la liste en JSON
    return response()->json([
        'status_code' => 200,
        'message' => 'Liste des rôles récupérée avec succès',
        'data' => $roles
    ]);
}

}
